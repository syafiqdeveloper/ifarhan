<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

//models
use App\Models\AccessroleIjnclinicaltc;
use App\Models\PatientInformation;
use App\Models\PatientSurgical;
use App\Models\PatientAllergy;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserAccessiccarole;

use DB;
use Auth;
use Carbon\Carbon;

class Authsystem
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $getStaffId         = $request->userid;
        $getStaffUsername   = $request->username;
        $getEpsdNo          = $request->epsdno;
        $getCodeAccess      = $request->codeaccess;
        $getHmilkAccess     = $request->hmilkaccess;
        $getPatid           = $request->patid;
        $getEpid            = $request->epid;
        $getUsrGrpId        = $request->usrGrpID;
        $getUsrGrp          = $request->usrGrp;
        $getUsrLocId        = $request->usrLocID;
        $getUsrLocDesc      = $request->usrLocDesc;

        if(isset($request->external) && $request->external == 'ICCA'){
            $staff = UserAccessiccarole::where('username', $request->username)
                        ->first();
                        
            $patient = Patient::where('mrn', $request->mrn)
            ->first();
            $getStaffId = $staff->tcuserid;
            $getUsrGrp = $staff->tcusergroup;
            $getUsrGrpId = $staff->tcusergroup_id;
            $getPatid = $patient->patid;
        }
        
        if($getEpsdNo != null)
        {
            if($getCodeAccess != 'iClinical1.0') {
                // Unauthorized response if token not there
                return response()->json([
                    'error' => 'Code denied'
                ], 401);
            }

            //start the transaction
            DB::beginTransaction();
 
            try
            {
                $urlAccess = env('STAFF_ACCESS').$getStaffId;

                //local
                $clientAccess = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

                //production
                //$clientAccess = new \GuzzleHttp\Client();

                $responseAccess = $clientAccess->request('GET', $urlAccess);

                $statusCodeAccess   = $responseAccess->getStatusCode();
                $contentAccess      = $responseAccess->getBody();
                $contentAccess      = json_decode($responseAccess->getBody(), true);

                $user = User::where('access_id', $contentAccess['tcid'])
                        ->first();
                
                if($user == null)
                {
                    $store = new User();
                    $store->access_id   = $contentAccess['tcid'];
                    $store->cp_id       = isset($contentAccess['uscpid']) && $contentAccess['uscpid'] != '' ? $contentAccess['uscpid'] : null;
                    $store->username    = $contentAccess['tcusername'];
                    $store->name        = $contentAccess['tcname'];
                    $store->mmcno       = isset($contentAccess['tcmmcno']) ? $contentAccess['tcmmcno'] : null;
                    $store->usergrpid   = $getUsrGrpId;
                    $store->usergrp     = $getUsrGrp;
                    $store->userlocid   = $getUsrLocId;
                    $store->userloc     = $getUsrLocDesc;
                    $store->status_id   = 2;
                    $store->created_at  = Carbon::now();
                    $store->save();
                }
                else
                {
                    $user->cp_id       = isset($contentAccess['uscpid']) && $contentAccess['uscpid'] != '' ? $contentAccess['uscpid'] : null;
                    $user->username    = $contentAccess['tcusername'];
                    $user->name        = $contentAccess['tcname'];
                    $user->mmcno       = isset($contentAccess['tcmmcno']) ? $contentAccess['tcmmcno'] : null;
                    $user->usergrpid   = $getUsrGrpId;
                    $user->usergrp     = $getUsrGrp;
                    $user->userlocid   = $getUsrLocId;
                    $user->userloc     = $getUsrLocDesc;
                    $user->status_id   = 2;
                    $user->updated_at  = Carbon::now();
                    $user->save();
                }
            }
            catch (\Exception $e)
            {
                $response = response()->json(
                    [
                      'status'  => 'failed',
                      'message' => "You don't have permission to login this system . Thank you."
                    ], 200
                );

                return $response;
            }
                            
            $userhold = User::where('access_id', $contentAccess['tcid'])
                        ->first();

            if(Auth::user() == null)
            {
                Auth::login($userhold);
            }
            else
            {
                if(Auth::user()->id != $userhold->id)
                {
                    Auth::login($userhold);
                }
            }

            // Now let's put the user in the request class so that you can grab it from there
            $request->auth = $user;

            $urlMed    = env('PAT_PREVIOUS').$getEpsdNo;

            //local
            $clientMed = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

            //production
            //$client = new \GuzzleHttp\Client();

            $holdMedication = null;
            $arrMedication  = [];
            
            try
            {
                $responseMed = $clientMed->request('GET', $urlMed);

                $statusCodeMed = $responseMed->getStatusCode();
                $contentMed    = $responseMed->getBody();
                $contentMed    = json_decode($responseMed->getBody(), true);

                if($contentMed != null)
                {
                    if($contentMed['status'] == "success")
                    {
                        $holdMedEpiNo   = $contentMed['data']['previousEpiNo'];
                        $holdMedEpiDate = $contentMed['data']['previousEpiDate'];

                        if(isset($contentMed['data']['medHistory']))
                        {
                            if(count($contentMed['data']['medHistory']) > 0)
                            {
                                foreach($contentMed['data']['medHistory'] as $medication)
                                {
                                    array_push($arrMedication, $medication['Itemdesc']);
                                }

                                $holdMedication = implode('; ', $arrMedication);
                            }
                            else
                            {
                                $holdMedication = null;
                            }
                        }
                        else
                        {
                            $holdMedication = null;
                        }
                    }
                    else
                    {
                        $holdMedication = null;
                    }
                }
                else
                {
                    $holdMedEpiNo   = null;
                    $holdMedEpiDate = null;
                    $holdMedication = null;
                }
            }
            catch (\Exception $e)
            {
                $holdMedEpiNo   = null;
                $holdMedEpiDate = null;
                $holdMedication = null;
            }

            $url    = env('PAT_DEMO').$getEpsdNo;

            //local
            $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

            //production
            //$client = new \GuzzleHttp\Client();

            $response = $client->request('GET', $url);

            $statusCode = $response->getStatusCode();
            $content    = $response->getBody();
            $content    = json_decode($response->getBody(), true);

            if($content['status'] == "success")
            {
                $holdPayor      = null;
                $arrPayor       = [];

                if(isset($content['data']['payorList']))
                {
                    if(count($content['data']['payorList']) > 0)
                    {
                        foreach($content['data']['payorList'] as $payor)
                        {
                            array_push($arrPayor, $payor['payorDesc']);
                        }

                        $holdPayor = implode(', ', $arrPayor);
                    }
                    else
                    {
                        $holdPayor = null;
                    }
                }
                else
                {
                    $holdPayor = null;
                }

                if(isset($content['data']['vitalSign']))
                {
                    if(isset($content['data']['vitalSign'][1]))
                    {
                        $holdsystolic   = $content['data']['vitalSign'][1]['systolic'] != '' ? str_replace('(mmHg)', '', $content['data']['vitalSign'][1]['systolic']) : null;
                        $holddiastolic  = $content['data']['vitalSign'][1]['diastolic'] != '' ? str_replace('(mmHg)', '', $content['data']['vitalSign'][1]['diastolic']) : null;
                        $holdheartrate  = $content['data']['vitalSign'][1]['pulse'] != '' ? str_replace('(bpm)', '', $content['data']['vitalSign'][1]['pulse']) : null;
                        $holdweight     = strtolower($content['data']['vitalSign'][1]['weight']) != '' && strtolower($content['data']['vitalSign'][1]['weight']) != 'unfit' ? str_replace('kg', '', strtolower($content['data']['vitalSign'][1]['weight'])) : null;
                        $holdheight     = strtolower($content['data']['vitalSign'][1]['height']) != '' && strtolower($content['data']['vitalSign'][1]['height']) != 'unfit' ? str_replace('cm', '', strtolower($content['data']['vitalSign'][1]['height'])) : null;
                        $holdheight     = ($holdheight == '' || $holdheight == 0) ? null : $holdheight;
                        $holdweight     = ($holdweight == '' || $holdweight == 0) ? null : $holdweight;
                        $holdspo2       = $content['data']['vitalSign'][1]['spo2'] != '' ? str_replace('%', '', $content['data']['vitalSign'][1]['spo2']) : null;
                        $holdlastupdate = $content['data']['vitalSign'][1]['lastUpdateDate'] != '' && $content['data']['vitalSign'][1]['lastUpdateTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['vitalSign'][1]['lastUpdateDate'])->format('Y-m-d').' '.$content['data']['vitalSign'][1]['lastUpdateTime'] : null;

                        if($holdweight != null && $holdheight != null)
                        {
                            $heightmeter = $holdheight/100;

                            $holdbmi = round($holdweight/pow(($heightmeter),2),2);
                            $holdbsa = round(0.007184 * pow($holdweight,0.425) * pow($holdheight,0.725),2);
                        }
                        else
                        {
                            $holdbmi = null;
                            $holdbsa = null;
                        }
                        $holdresprate = null;
                    }
                    else
                    {
                        $holdsystolic   = $content['data']['systolic'] != '' ? $content['data']['systolic'] : null;
                        $holddiastolic  = $content['data']['diastolic'] != '' ? $content['data']['diastolic'] : null;
                        $holdheartrate  = $content['data']['HeartRate'] != '' ? $content['data']['HeartRate'] : null;
                        $holdheartratedate = $content['data']['HeartRateDate'] != '' && $content['data']['HeartRateTime'] != '' ? $content['data']['HeartRateDate'].' '.$content['data']['HeartRateTime'] : null;
                        $holdpulse      = $content['data']['pulse'] != '' ? $content['data']['pulse'] : null;
                        $holdpulsedate  = $content['data']['pulseDate'] != '' && $content['data']['pulseTime'] != '' ? $content['data']['pulseDate'].' '.$content['data']['pulseTime'] : null;
                        $holdweight     = $content['data']['weight'] != '' && strtolower($content['data']['weight']) != 'unfit' ? $content['data']['weight'] : null;
                        $holdheight     = $content['data']['height'] != '' && strtolower($content['data']['height']) != 'unfit' ? $content['data']['height'] : null;
                        $holdspo2       = $content['data']['spo2'] != '' ? $content['data']['spo2'] : null;
                        $holdbmi        = $content['data']['bmi'] != '' ? $content['data']['bmi'] : null;
                        $holdbsa        = $content['data']['height'] != '' && strtolower($content['data']['height']) != 'unfit' && $content['data']['weight'] != '' && $content['data']['weight'] != 'unfit' ? round(0.007184 * pow($content['data']['weight'],0.425) * pow($content['data']['height'],0.725),2) : null;
                        $holdresprate   = $content['data']['resprate'] != '' ? $content['data']['resprate'] : null;
                        $holdlastupdate = $content['data']['vupdDate'] != '' && $content['data']['vUpdTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['vupdDate'])->format('Y-m-d').' '.$content['data']['vUpdTime'] : null;
                    }
                }
                else
                {
                    $holdsystolic   = $content['data']['systolic'] != '' ? $content['data']['systolic'] : null;
                    $holddiastolic  = $content['data']['diastolic'] != '' ? $content['data']['diastolic'] : null;
                    $holdheartrate  = $content['data']['HeartRate'] != '' ? $content['data']['HeartRate'] : null;
                    $holdheartratedate = $content['data']['HeartRateDate'] != '' && $content['data']['HeartRateTime'] != '' ? $content['data']['HeartRateDate'].' '.$content['data']['HeartRateTime'] : null;
                    $holdpulse      = $content['data']['pulse'] != '' ? $content['data']['pulse'] : null;
                    $holdpulsedate  = $content['data']['pulseDate'] != '' && $content['data']['pulseTime'] != '' ? $content['data']['pulseDate'].' '.$content['data']['pulseTime'] : null;
                    $holdweight     = $content['data']['weight'] != '' && strtolower($content['data']['weight']) != 'unfit' ? $content['data']['weight'] : null;
                    $holdheight     = $content['data']['height'] != '' && strtolower($content['data']['height']) != 'unfit' ? $content['data']['height'] : null;
                    $holdspo2       = $content['data']['spo2'] != '' ? $content['data']['spo2'] : null;
                    $holdbmi        = $content['data']['bmi'] != '' ? $content['data']['bmi'] : null;
                    $holdbsa        = $content['data']['height'] != '' && strtolower($content['data']['height']) != 'unfit' && $content['data']['weight'] != '' && strtolower($content['data']['weight']) != 'unfit' ? round(0.007184 * pow($content['data']['weight'],0.425) * pow($content['data']['height'],0.725),2) : null;
                    $holdlastupdate = $content['data']['vupdDate'] != '' && $content['data']['vUpdTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['vupdDate'])->format('Y-m-d').' '.$content['data']['vUpdTime'] : null;
                    $holdresprate   = $content['data']['resprate'] != '' ? $content['data']['resprate'] : null;
                }

                $holdmrnpatient = $content['data']['prn'];

                if($holdmrnpatient != null)
                {
                    $checkIfMrnExist = Patient::where('mrn', $content['data']['prn'])
                                        ->where('status_id', 2)
                                        ->first();

                    if($checkIfMrnExist == null)
                    {
                        $storePatient = new Patient();
                        $storePatient->patid        = $getPatid;
                        $storePatient->mrn          = $content['data']['prn'];
                        $storePatient->name         = $content['data']['pName'];
                        $storePatient->nric         = $content['data']['pnric'];
                        $storePatient->dob          = $content['data']['pdob'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['pdob'])->format('Y-m-d') : null;
                        $storePatient->sex          = $content['data']['pgender'];
                        $storePatient->status_id    = 2;
                        $storePatient->created_by   = Auth::user()->id;
                        $storePatient->created_at   = Carbon::now();
                        $storePatient->save();

                        $holpatientid = $storePatient['id'];

                        $storeInfo = new PatientInformation();
                        $storeInfo->episodenumber           = $getEpsdNo;
                        $storeInfo->epid                    = $getEpid;
                        $storeInfo->epsiodedate             = $content['data']['epiDate'] != '' && $content['data']['epiTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['epiDate'])->format('Y-m-d').' '.$content['data']['epiTime'] : null;
                        $storeInfo->admissiondate           = $content['data']['epiDate'] != '' && $content['data']['epiTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['epiDate'])->format('Y-m-d').' '.$content['data']['epiTime'] : null;
                        $storeInfo->dischargedate           = $content['data']['epiDiscDate'] != '' && $content['data']['epiDiscTime'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['epiDiscDate'])->format('Y-m-d').' '.$content['data']['epiDiscTime'] : null;
                        $storeInfo->episodedept             = $content['data']['epiDept'] != '' ? $content['data']['epiDept'] : null;
                        $storeInfo->episodedeptname         = $content['data']['epiDeptDesc'] != '' ? $content['data']['epiDeptDesc'] : null;
                        $storeInfo->consultantname          = $content['data']['epiDoc'] != '' ? $content['data']['epiDoc'] : null;
                        $storeInfo->padd                    = $content['data']['padd'] != '' ? $content['data']['padd'] : null;
                        $storeInfo->pposcode                = $content['data']['pposcode'] != '' ? $content['data']['pposcode'] : null;
                        $storeInfo->pcity                   = $content['data']['pcity'] != '' ? $content['data']['pcity'] : null;
                        $storeInfo->pstate                  = $content['data']['pstate'] != '' ? $content['data']['pstate'] : null;
                        $storeInfo->pcountry                = $content['data']['pcountry'] != '' ? $content['data']['pcountry'] : null;
                        $storeInfo->patient_id              = $storePatient->id;
                        $storeInfo->age                     = $content['data']['age'] != '' ? $content['data']['age'] : null;
                        $storeInfo->agem                    = $content['data']['ageM'] != '' ? $content['data']['ageM'] : null;
                        $storeInfo->aged                    = $content['data']['ageD'] != '' ? $content['data']['ageD'] : null;
                        $storeInfo->epiward                 = $content['data']['epiWard'] != '' ? $content['data']['epiWard'] : null;
                        $storeInfo->epiroom                 = $content['data']['epiRoom'] != '' ? $content['data']['epiRoom'] : null;
                        $storeInfo->epistat                 = $content['data']['epiStat'] != '' ? $content['data']['epiStat'] : null;
                        $storeInfo->bloodtype               = $content['data']['bgDesc'] != '' ? $content['data']['bgDesc'] : null;
                        $storeInfo->unfit                   = $content['data']['epiunfit'] == 1 ? 1 : null;
                        $storeInfo->pchcflag                = $content['data']['pchcFlag'] != '' ? $content['data']['pchcFlag'] : null;
                        $storeInfo->phosp                   = $content['data']['pHosp'] != '' ? $content['data']['pHosp'] : null;
                        $storeInfo->height                  = $holdheight;
                        $storeInfo->weight                  = $holdweight;
                        $storeInfo->bmi                     = $holdbmi;
                        $storeInfo->sysbp                   = $holdsystolic;
                        $storeInfo->diasbp                  = $holddiastolic;
                        $storeInfo->heartrate               = $holdheartrate;
                        $storeInfo->heartrate_datetime      = $holdheartratedate;
                        $storeInfo->pulse                   = $holdpulse;
                        $storeInfo->pulse_datetime          = $holdpulsedate;
                        $storeInfo->spo2                    = $holdspo2;
                        $storeInfo->resprate                = $holdresprate;
                        $storeInfo->bsa                     = $holdbsa;
                        $storeInfo->temperature             = $content['data']['temp'] != '' ? $content['data']['temp'] : null;
                        $storeInfo->epiinterv               = $content['data']['epiInterv'] != '' ? $content['data']['epiInterv'] : null;
                        $storeInfo->vs_datetime             = $holdlastupdate;
                        $storeInfo->payor                   = $holdPayor;
                        $storeInfo->currentmedication       = $holdMedication;
                        $storeInfo->medicationepisodeno     = $holdMedEpiNo;
                        $storeInfo->medicationepisodedate   = $holdMedEpiDate != null ? Carbon::createFromFormat('d/m/Y', $holdMedEpiDate)->format('Y-m-d') : null;
                        $storeInfo->dischargedate           = $content['data']['epiDiscDate'] != '' ? Carbon::createFromFormat('d/m/Y', $content['data']['epiDiscDate'])->format('Y-m-d') : null;
                        $storeInfo->ef                      = $content['data']['ef'] != '' ? $content['data']['ef'] : null;
                        $storeInfo->efc                     = $content['data']['efc'] != '' ? $content['data']['efc'] : null;
                        $storeInfo->painscore               = null;
                        $storeInfo->painsite                = null;
                        $storeInfo->status_id               = 2;
                        $storeInfo->created_by              = Auth::user()->id;
                        $storeInfo->created_at              = Carbon::now();
                        $storeInfo->save();
                    }
                }
                else
                {
                    $holdmrnpatient = null;

                    DB::rollBack();

                    $response = response()->json(
                        [
                          'status'  => 'failed',
                          'message' => 'No MRN'
                        ], 200
                    );

                    return $response;
                }
            }
            else
            {
                $holdmrnpatient = null;

                DB::rollBack();

                $response = response()->json(
                    [
                      'status'  => 'failed',
                      'message' => 'API Error'
                    ], 200
                );

                return $response;
            }

            //commit the transaction
            DB::commit();

            return $next($request);
        }
        else
        {
            $ipharmacyArr = [
                'ipharmacy',
                'ipharmacy/getcheckdata',
                'ipharmacy/updatecheckdata', 
                'ipharmacy/getcollectdata', 
                'ipharmacy/updatecollectdata', 
                'ireporting/imilk', 
                'ireporting/imilk/getimilkinventory',
                'ireporting/iblood', 
                'ireporting/iblood/getibloodinventory',
                'ireporting/iblood/getlocationdetails',
                'ireporting/iblood/getsingleinventory',
                'ireporting/iblood/updateibloodinv',
                'ireporting/iblood/atr', 
                'ireporting/iblood/atr/getworklist',
                'ireporting/iblood/atr/getworklistconfirm',
                'ireporting/iblood/atr/getworklistfalse',
                'ireporting/iblood/atr/generateconfirm',
                'blood/reaction/report/generate',
                'ireporting/ida/preadmission',
                'ireporting/ida/preadmission/getpreadmissioninventory',
                'ireporting/discharge-summary',
                'ireporting/discharge-summary/list',
                'ireporting/pending-discharge-summary',
                'ireporting/pending-discharge-summary/list',
                'ireporting/adr',
                'ireporting/adr/getworklistsuspect',
                'ireporting/adr/getworklistconfirm',
                'ireporting/adr/getworklistfalse',
                'ireporting/adr/generateconfirm',
                'ireporting/adr/generatesuspect',
                'ireporting/adr/getpatientinfo',
                'ireporting/medshelf',
                'ireporting/medshelf/list',
                'ireporting/consent',
                'ireporting/consent/list',
                'ireporting/inursing/wardorientation',
                'ireporting/inursing/wardorientation/orientation/list',
                'ireporting/inursing/wardorientation/transfer/list',
                'ireporting/inursing/bedsidemobilityassmnt',
                'ireporting/inursing/bedsidemobilityassmnt/list',
                'ireporting/inursing/safetychecklist',
                'ireporting/inursing/safetychecklist/list',
                'ireporting/inursing/dysphagia',
                'ireporting/inursing/dysphagia/list',
                'ireporting/inursing/peritonealchart',
                'ireporting/inursing/peritonealchart/list',
                'ireporting/inursing/limbrestraint',
                'ireporting/inursing/limbrestraint/list',
                'ireporting/inursing/miscellaneous',
                'ireporting/inursing/miscellaneous/list',
                'ireporting/inursing/patientassmtchecklist',
                'ireporting/inursing/patientassmtchecklist/list',
                'ireporting/inursing/homeassmtchecklist',
                'ireporting/inursing/homeassmtchecklist/list',
                'ireporting/inursing/dischargechecklist',
                'ireporting/inursing/dischargechecklist/list',
                'ireporting/inursing/postdischarge',
                'ireporting/inursing/postdischarge/list',
                'bed-management',
                'bed-management/getwardlist',
                'bed-management/getpatientinfo',


            ];
            if(in_array($request->path(), $ipharmacyArr)){
                try
                {
                    if($getStaffId == null){
                        $getStaffId = $request->userId;
                    }

                    $urlAccess = env('STAFF_ACCESS').$getStaffId;

                    //local
                    $clientAccess = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

                    //production
                    //$clientAccess = new \GuzzleHttp\Client();

                    $responseAccess = $clientAccess->request('GET', $urlAccess);

                    $statusCodeAccess   = $responseAccess->getStatusCode();
                    $contentAccess      = $responseAccess->getBody();
                    $contentAccess      = json_decode($responseAccess->getBody(), true);

                    $user = User::where('access_id', $contentAccess['tcid'])
                            ->first();
                    
                    if($user == null)
                    {
                        $store = new User();
                        $store->access_id   = $contentAccess['tcid'];
                        $store->username    = $contentAccess['tcusername'];
                        $store->name        = $contentAccess['tcname'];
                        $store->mmcno       = isset($contentAccess['tcmmcno']) ? $contentAccess['tcmmcno'] : null;
                        $store->usergrpid   = $getUsrGrpId;
                        $store->usergrp     = $getUsrGrp;
                        $store->userlocid   = $getUsrLocId;
                        $store->userloc     = $getUsrLocDesc;
                        $store->status_id   = 2;
                        $store->created_at  = Carbon::now();
                        $store->save();
                    }
                    else
                    {
                        $user->username    = $contentAccess['tcusername'];
                        $user->name        = $contentAccess['tcname'];
                        $user->mmcno       = isset($contentAccess['tcmmcno']) ? $contentAccess['tcmmcno'] : null;
                        $user->usergrpid   = $getUsrGrpId;
                        $user->usergrp     = $getUsrGrp;
                        $user->userlocid   = $getUsrLocId;
                        $user->userloc     = $getUsrLocDesc;
                        $user->status_id   = 2;
                        $user->updated_at  = Carbon::now();
                        $user->save();
                    }
                }
                catch (\Exception $e)
                {
                    $response = response()->json(
                        [
                        'status'  => 'failed',
                        'message' => "You don't have permission to login this system . Thank you."
                        ], 200
                    );

                    return $response;
                }
                return $next($request);
            }
            else{
                $response = response()->json(
                    [
                      'status'  => 'failed',
                      'message' => "Please login via TrakCare. Thank you."
                    ], 200
                );
    
                return $response;
            }
            
        }
    }
}
