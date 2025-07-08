<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Models\Inventory;
use App\Models\BloodInventory;
use App\Models\BloodLocation;
use App\Models\BloodDetailProcedure;
use App\Models\BloodSignSymptom;
use App\Models\BloodTypeAdverseEvent;
use App\Models\BloodOutcomeAdverseEvent;
use App\Models\BloodRelevantInvestigation;
use App\Models\BloodRelevantHistory;
use App\Models\BloodBloodComponent;
use App\Models\Patient;
use App\Models\PatientInformation;
use App\Models\Dischargesummary;
use App\Models\AdrReport;
use App\Models\AdrList;
use App\Models\AdrBridge;
use App\Models\MedShelf;
use App\Models\MedShelfUser;
use App\Models\MedShelfUserSSO;
use App\Models\Sso;
use App\Models\BloodWardLocation;
use App\Models\ConsentList;


use DB;
use Auth;

class iReportingMainController extends Controller
{
    public function indexImilk(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        $wardlist = BloodWardLocation::all();

        if($request->usrGrp == "EMY" || $request->usrGrp == "EMYDoctors" || $request->usrGrp == "ICLNurse" || $request->usrGrp == "OTNurse"){

            //Issued
            $totalissuedactive            = BloodInventory::where('transfuse_status_id', '!=' , 7)->count();
            $totalissuedreturnedused      = BloodInventory::where('transfuse_status_id', 7)->where('transfuse_completion_id' , 2)->count();
            $totalissuedreturnednotused   = BloodInventory::where('transfuse_status_id', 7)->where('transfuse_completion_id' , null)->count();
            $totalissued                  = $totalissuedreturnedused + $totalissuedactive + $totalissuedreturnednotused;

            //Transfused
            $pending              = BloodInventory::where('transfuse_completion_id' , null)->where('transfuse_status_id' , '!=' , 7)->count();
            $inprogress           = BloodInventory::where('transfuse_start_at' , '!=', null)->where('transfuse_stop_at' , null)->where('transfuse_status_id' , '!=' , 5)->count();
            $completed            = BloodInventory::where('transfuse_completion_id' , 2)->count();
            $totaltransfuse       = $pending + $inprogress + $completed;


            //ATR
            $confirm              = BloodDetailProcedure::where('status_id' , 1)->count();
            $false                = BloodDetailProcedure::where('status_id' , 3)->count();
            $totalyesreaction     = BloodInventory::where('reaction' , 'Yes')->count();
            $totalatr             = $confirm + $false + $totalyesreaction;

            //Stored
            $expired       = BloodInventory::where('expiry_date', '!=', null)->where('expiry_date', '<', Carbon::now())->where('transfuse_status_id', 2)->count();
            $notexpired    = BloodInventory::where('expiry_date', '!=', null)->where('expiry_date', '>=', Carbon::now())->where('transfuse_status_id', 2)->count();      
            $totalstored   = $expired + $notexpired;

            return view('ireporting.iblood.index', compact(
                'url', 
                'totalissuedactive', 
                'totalissuedreturnedused',
                'totalissuedreturnednotused',  
                'totalissued',
                'totaltransfuse',
                'totalyesreaction',
                'pending',
                'inprogress',
                'completed',
                'confirm',
                'false',
                'expired',
                'notexpired',
                'totalstored',
                'totalatr',
                'wardlist'
             ));
                         
        }elseif($request->usrGrp == "LABManager" || $request->usrGrp == "LABMLT" || $request->usrGrp == "LABTemp" || $request->usrGrp == "LABClerk" || $request->usrGrp == "QualityManagement"){

            return view('ireporting.iblood.atrworklist', compact('url'));
   
        }elseif($request->usrGrp == "Administrator" || $request->usrGrp == "Doctors" || $request->usrGrp == "WardNurse" || $request->usrGrp == "WardNursePrivate" || $request->usrGrp == "WardClerk" || $request->usrGrp == "WardManagerOrMentor" || $request->usrGrp == "OPDNurse"){
        
            //TotalEBM
            $totalebm        = Inventory::all()->count();
            $totalebmChiller = Inventory::where('status', 1)->where('storeArea', "Chiller")->count();
            $totalebmFreezer = Inventory::where('status', 1)->where('storeArea', "Freezer")->count();

            //ExpiredEBM
            $expiredebmChiller = Inventory::where('status', 1)->where('expiryDate', '<', Carbon::now())->where('storeArea', "Chiller")->count();
            $expiredebmFreezer = Inventory::where('status', 1)->where('expiryDate', '<', Carbon::now())->where('storeArea', "Freezer")->count();
            $expiredebm        = $expiredebmChiller + $expiredebmFreezer;

            //Pending
            $handover = Inventory::where('status', 4)->count();
            $prepare  = Inventory::where('status', 2)->count();
            $pending  = $handover + $prepare;

            //Administered
            $administered = Inventory::where('status', 3)->count();

            return view('ireporting.imilk.index', compact('url', 'totalebm', 'totalebmChiller','totalebmFreezer', 'expiredebm', 'expiredebmChiller','expiredebmFreezer', 'pending', 'handover','prepare', 'administered', 'wardlist'));

        }elseif($request->usrGrp == "MROffice"){

            return view('ireporting.dischargesummary.index', compact('url'));

        }else{

            return view('ireporting.noaccess', compact('url'));

        }

    }

    public function getImilkInventory(Request $request)
    {
        $data = DB::table('hmilk_inventories as inv')
        ->selectRaw('pats.mrn, inv.episodeNo, patinfo.epsiodedate, inv.batchId, inv.location, inv.created_at, inv.expiryDate, u.username, inv.status')
        ->leftJoin('patient_information as patinfo', 'inv.episodeNo', '=', 'patinfo.episodenumber')
        ->leftJoin('patients as pats', 'patinfo.patient_id', '=', 'pats.id')
        ->leftJoin('users as u', 'u.id', '=', 'inv.created_by');

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $data->whereBetween('inv.created_at', [$startDate, $endDate]);
        }

        // Filter by Status
        if ($request->has('status') && $request->status != 'all') {
            $data->where('inv.status', $request->status);
        }

        return response()->json(['data' => $data->get()]);

    }

    public function indexIblood(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        $wardlist = BloodWardLocation::all();

        //Issued
        $totalissuedactive            = BloodInventory::where('transfuse_status_id', '!=' , 7)->count();
        $totalissuedreturnedused      = BloodInventory::where('transfuse_status_id', 7)->where('transfuse_completion_id' , 2)->count();
        $totalissuedreturnednotused   = BloodInventory::where('transfuse_status_id', 7)->where('transfuse_completion_id' , null)->count();
        $totalissued                  = $totalissuedreturnedused + $totalissuedactive + $totalissuedreturnednotused;

        //Transfused
        $pending              = BloodInventory::where('transfuse_completion_id' , null)->where('transfuse_status_id' , '!=' , 7)->count();
        $inprogress           = BloodInventory::where('transfuse_start_at' , '!=', null)->where('transfuse_stop_at' , null)->where('transfuse_status_id' , '!=' , 5)->count();
        $completed            = BloodInventory::where('transfuse_completion_id' , 2)->count();
        $totaltransfuse       = $pending + $inprogress + $completed;


        //ATR
        $confirm              = BloodDetailProcedure::where('status_id' , 1)->count();
        $false                = BloodDetailProcedure::where('status_id' , 3)->count();
        $totalyesreaction     = BloodInventory::where('reaction' , 'Yes')->count();
        $totalatr             = $confirm + $false + $totalyesreaction;

        //Stored
        $expired       = BloodInventory::where('expiry_date', '!=', null)->where('expiry_date', '<', Carbon::now())->where('transfuse_status_id', 2)->count();
        $notexpired    = BloodInventory::where('expiry_date', '!=', null)->where('expiry_date', '>=', Carbon::now())->where('transfuse_status_id', 2)->count();      
        $totalstored   = $expired + $notexpired;

        return view('ireporting.iblood.index', compact(
            'url', 
            'totalissuedactive', 
            'totalissuedreturnedused',
            'totalissuedreturnednotused',  
            'totalissued',
            'totaltransfuse',
            'totalyesreaction',
            'pending',
            'inprogress',
            'completed',
            'confirm',
            'false',
            'expired',
            'notexpired',
            'totalstored',
            'totalatr',
            'wardlist'
         ));

    }

    public function getIbloodInventory(Request $request)
    {
        $inventory = BloodInventory::with([
            'locations.transfer_by:id,name',
            'locations.user:id,name', // Include transfer_by relation within locations
            'user:id,name',
            'transfuse_start_by:id,name',
            'transfuse_verify_by:id,name',
            'patinfo.patient' // Include patient relation within patinfo
        ])->orderBy('id', 'desc');

            // Filter by Date Range
            if ($request->has('dateRange')) {
                $dateRange = explode(' - ', $request->dateRange);
                $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
                $inventory->whereBetween('created_at', [$startDate, $endDate]);
            }

            // // Filter by Status
            // if ($request->has('status') && $request->status != 'all') {
            //     $data->where('inv.transfuse_status_id', $request->status);
            // }
    
        return response()->json(['data' => $inventory->get()]);
    }

    public function getIbloodLocationDetails(Request $request)
    {

        $inventory = BloodInventory::where('bagno', $request->bagNo)->where('episodeno', $request->episodeNo)->where('labno', $request->labNo)->with([
            'locations' => function ($query) use ($request) {
                $query->where('episodeno', $request->episodeNo); // Ensure locations are tied to the specific inventory
            },
            'locations.transfer_by:id,name',
            'locations.user:id,name', // Include transfer_by relation within locations
            'user:id,name',
            'transfuse_start_by:id,name',
            'transfuse_verify_by:id,name',
        ]);
      
        return response()->json(['data' => $inventory->first()]);
    }

    public function indexIbloodAtr(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        return view('ireporting.iblood.atrworklist', compact('url'));

    }

    public function getIBloodAtrWorklist(Request $request)
    {
        // $data = DB::table('iblood_inventories as inv')
        // ->leftJoin('patient_information as patinfo', 'inv.episodeno', '=', 'patinfo.episodenumber')
        // ->leftJoin('patients as pats', 'patinfo.patient_id', '=', 'pats.id')
        // ->leftJoin('iblood_relevantinvestigation as ri', 'inv.bagno', '=', 'ri.inventory_bagno')
        // ->leftJoin('users as u', 'ri.created_by', '=', 'u.id')
        // ->leftJoin('iblood_locations as l', 'inv.bagno', '=', 'l.inventory_bagno')
        // ->selectRaw('
        //     pats.mrn, 
        //     inv.episodeno, 
        //     inv.bagno, 
        //     inv.transfuse_start_at, 
        //     inv.expiry_date, 
        //     inv.transfuse_status_id, 
        //     ri.created_at, 
        //     u.name, 
        //     inv.transfuse_stop_at, 
        //     ri.status_id,
        //     l.location,
        //     l.stop_transfusion
        // ')
        // ->where(function ($query) {
        //     $query->where('inv.atr_status_id', 1)
        //           ->where('l.stop_transfusion','!=', null);

        // });

        $data = BloodInventory::with([
            'locs' => function ($query) use ($request) {
                $query->where('stop_transfusion', '!=' , null);
            },
            'locs.transfer_by:id,name',
            'locs.user:id,name', 
            'user:id,name',
            'transfuse_start_by:id,name',
            'transfuse_verify_by:id,name',
            'patinfo.patient',
            'detailprocedures' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'bloodcomponents' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'clinicalhistories' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'symptoms' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'investigations' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'adverseoutcomes' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'adverseevents' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
        ])->orderBy('id', 'desc')->where('atr_status_id', 1);

        // dd($data->get());

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $data->whereBetween('transfuse_stop_at', [$startDate, $endDate]);
        }   

        return response()->json(['data' => $data->get()]);

    }

    public function getIBloodAtrWorklistConfirm(Request $request)
    {
        $data = DB::table('iblood_inventories as inv')
        ->leftJoin('patient_information as patinfo', 'inv.episodeno', '=', 'patinfo.episodenumber')
        ->leftJoin('patients as pats', 'patinfo.patient_id', '=', 'pats.id')
        ->leftJoin('iblood_relevantinvestigation as ri', 'inv.bagno', '=', 'ri.inventory_bagno')
        ->leftJoin('users as u', 'ri.created_by', '=', 'u.id')
        ->leftJoin('iblood_locations as l', 'inv.bagno', '=', 'l.inventory_bagno')
        ->selectRaw('
            pats.mrn, 
            inv.episodeno, 
            inv.bagno, 
            inv.transfuse_start_at, 
            inv.expiry_date, 
            inv.transfuse_status_id, 
            ri.created_at, 
            u.name, 
            inv.transfuse_stop_at, 
            ri.status_id,
            l.location,
            l.stop_transfusion
        ')
        ->where(function ($query) {
            $query->where('inv.atr_status_id', 2)
                  ->where('l.stop_transfusion','!=', null);
        });

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $data->whereBetween('inv.created_at', [$startDate, $endDate]);
        }
    
        return response()->json(['data' => $data->get()]);

    }

    public function getIBloodAtrWorklistFalse(Request $request)
    {
        $data = DB::table('iblood_inventories as inv')
        ->leftJoin('patient_information as patinfo', 'inv.episodeno', '=', 'patinfo.episodenumber')
        ->leftJoin('patients as pats', 'patinfo.patient_id', '=', 'pats.id')
        ->leftJoin('iblood_relevantinvestigation as ri', 'inv.bagno', '=', 'ri.inventory_bagno')
        ->leftJoin('users as u', 'ri.created_by', '=', 'u.id')
        ->leftJoin('iblood_locations as l', 'inv.bagno', '=', 'l.inventory_bagno')
        ->selectRaw('
            pats.mrn, 
            inv.episodeno, 
            inv.bagno, 
            inv.transfuse_start_at, 
            inv.expiry_date, 
            inv.transfuse_status_id, 
            ri.created_at, 
            u.name, 
            inv.transfuse_stop_at, 
            ri.status_id,
            l.location,
            l.stop_transfusion
        ')
        ->where(function ($query) {
            $query->where('inv.atr_status_id', 3)
                  ->where('l.stop_transfusion','!=', null);
        });

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $data->whereBetween('inv.created_at', [$startDate, $endDate]);
        }

        return response()->json(['data' => $data->get()]);

    }

    public function indexPreAdmission(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        return view('ireporting.ida.preadmission', compact('url'));

    }

    //begin discharge summary
        public function indexDischargeSummary(Request $request)
        {
            $explode = explode('?', $request->getRequestUri());

            $url = $explode[1];

            return view('ireporting.dischargesummary.index', compact('url'));
        }

        public function apiGetDataDischargeSummary(Request $request)
        {
            try
            {
                $dateRange  = explode(' - ', $request->dateRange);
                $startDate  = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
                $endDate    = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();

                $hasDs = Dischargesummary::whereBetween('dischargedate', [$startDate, $endDate])
                            ->with('createdby', function($q){
                                $q->select('id', 'name');
                            })
                            ->with('updatedby', function($q){
                                $q->select('id', 'name');
                            })
                            ->where('status_id', 2)
                            ->orderBy('dischargedate', 'desc')
                            ->get();

                $data = [];

                // dd($hasDs);

                foreach($hasDs as $ds)
                {
                    $temp = [];

                    $getPatInfo = PatientInformation::where('status_id', 2)
                                    ->select('id', 'patient_id', 'episodenumber', 'epsiodedate')
                                    ->where('id', $ds['patientinformation_id'])
                                    ->with('patient', function($q){
                                        $q->select('id', 'mrn', 'name');
                                    })
                                    ->first();

                    $temp['name']                       = $getPatInfo['patient']['name'];
                    $temp['mrn']                        = $getPatInfo['patient']['mrn'];
                    $temp['episode']                    = $getPatInfo['episodenumber'];
                    $temp['episodedate']                = Carbon::parse($getPatInfo['epsiodedate'])->format('Y-m-d');
                    $temp['dischargedate']              = Carbon::parse($ds['dischargedate'])->format('Y-m-d');
                    $temp['reasonforadmission']         = $ds['reasonforadmission'];
                    $temp['primarydiagnosis']           = $ds['primarydiagnosis'];
                    $temp['secondarydiagnosis']         = $ds['secondarydiagnosis'];
                    $temp['previoussurgery']            = $ds['previoussurgery'];
                    $temp['relevantphysicalfinding']    = $ds['relevantphysicalfinding'];
                    $temp['principalprocedurefinding']  = $ds['principalprocedurefinding'];
                    $temp['briefhospitalcourse']        = $ds['briefhospitalcourse'];
                    $temp['briefhospitalcoursedesc']    = $ds['briefhospitalcoursedesc'];
                    $temp['significantinpatientmed']    = $ds['significantinpatientmed'];
                    $temp['conditionpatientdis']        = $ds['conditionpatientdis'];
                    $temp['dischargemedication']        = $ds['dischargemedication'];
                    $temp['followupcareclinicvisit']    = $ds['followupcareclinicvisit'];
                    $temp['planmanagement']             = $ds['planmanagement'];
                    $temp['referringdoctoraddress']     = $ds['referringdoctoraddress'];
                    $temp['finalby']                    = $ds['finalby'];
                    $temp['createdby']                  = $ds['createdby']['name'];
                    $temp['createdat']                  = $ds['created_at'];
                    $temp['updatedby']                  = $ds['updatedby'] != null ? $ds['updatedby']['name'] : null;
                    $temp['updatedat']                  = $ds['updated_at'] != null ? $ds['updated_at'] : null;

                    array_push($data, $temp);
                }

                $response = response()->json(
                    [
                      'status'  => 'success',
                      'data'    => $data
                    ], 200
                );

                return $response;
            }
            catch(\Exception $e)
            {
                Log::error($e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                );

                $response = response()->json(
                    [
                        'status'  => 'failed',
                        'message' => 'Internal error happened. Try again'
                    ], 200
                );

                return $response;
            }
        }
    //end discharge esummary

    //begin discharge summary unfinalized
        public function indexDischargeSummaryUnfinalized(Request $request)
        {
            $explode = explode('?', $request->getRequestUri());

            $url = $explode[1];

            return view('ireporting.dischargesummaryunfinalized.index', compact('url'));
        }

        public function apiGetDataDischargeSummaryUnfinalized(Request $request)
        {
            try
            {
                $dateRange  = explode(' - ', $request->dateRange);
                $startDate  = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
                $endDate    = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();

                $hasDs = Dischargesummary::whereBetween('dischargedate', [$startDate, $endDate])
                            ->with('createdby', function($q){
                                $q->select('id', 'name');
                            })
                            ->with('updatedby', function($q){
                                $q->select('id', 'name');
                            })
                            ->with('draftsaveby', function($q){
                                $q->select('id', 'name');
                            })
                            ->whereNull('finalsaveby')
                            ->where('status_id', 2)
                            ->orderBy('dischargedate', 'desc')
                            ->get();

                $data = [];

                // dd($hasDs);

                foreach($hasDs as $ds)
                {
                    $temp = [];

                    $getPatInfo = PatientInformation::where('status_id', 2)
                                    ->select('id', 'patient_id', 'episodenumber', 'epsiodedate', 'consultantname')
                                    ->where('id', $ds['patientinformation_id'])
                                    ->with('patient', function($q){
                                        $q->select('id', 'mrn', 'name');
                                    })
                                    ->first();

                    $temp['mrn']                        = $getPatInfo['patient']['mrn'];
                    $temp['name']                       = $getPatInfo['patient']['name'];
                    $temp['consultantname']             = $getPatInfo['consultantname'];
                    $temp['episode']                    = $getPatInfo['episodenumber'];
                    $temp['ward']                       = $getPatInfo['epiward'];
                    $temp['dischargedate']              = Carbon::parse($ds['dischargedate'])->format('Y-m-d');
                    $temp['dischargedsummaryentry']     = Carbon::parse($ds['created_at'])->format('Y-m-d');
                    $temp['finalby']                    = $ds['finalby'];
                    $temp['totaldayspending']           = 0;
                    $temp['savedraftby']                = $ds['draftsaveby'] != null ? $ds['draftsaveby']['name'] : null;
                    $temp['savedraftat']                = $ds['draftsaveat'] != null ? $Carbon::parse($ds['draftsaveat'])->format('Y-m-d') : null;

                    array_push($data, $temp);
                }

                $response = response()->json(
                    [
                      'status'  => 'success',
                      'data'    => $data
                    ], 200
                );

                return $response;
            }
            catch(\Exception $e)
            {
                Log::error($e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                );

                $response = response()->json(
                    [
                        'status'  => 'failed',
                        'message' => 'Internal error happened. Try again'
                    ], 200
                );

                return $response;
            }
        }
    //end discharge summary unfinalized

    public function genReportConfirm(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        $bagno = $request->bagno;
        $episode = $request->epsdno;

        //DETAIL PROCEDURE
        $procedure = BloodDetailProcedure::where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        //SECTION D
        $component = BloodBloodComponent::where('inventory_bagno', $bagno)->where('status_id', '2')->first();
        if ($component) {
            $component->product = explode(', ', $component->product);
        }
        
        //Section F
        $relevanthistory = BloodRelevantHistory::where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        //Section G
        $record = BloodSignSymptom::where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        if ($record) {
            $record->general = explode(', ', $record->general);
            $record->pain = explode(', ', $record->pain);
            $record->renal = explode(', ', $record->renal);
            $record->respiratory = explode(', ', $record->respiratory);
            $record->skin = explode(', ', $record->skin);
            $record->cardio = explode(', ', $record->cardio);
        }

        //Section J
        $typeadverseevent = BloodTypeAdverseEvent::where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        if ($typeadverseevent) {
            $typeadverseevent->section1 = explode(', ', $typeadverseevent->section1);
            $typeadverseevent->section5 = explode(', ', $typeadverseevent->section5);
        }

        //Section I
        $outcomeadverseevent = BloodOutcomeAdverseEvent::where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        if ($outcomeadverseevent) {
            $outcomeadverseevent->recovered = explode(', ', $outcomeadverseevent->recovered);
            $outcomeadverseevent->death     = explode(', ', $outcomeadverseevent->death);
        }

        //Section H
        $relevantinvestigation = BloodRelevantInvestigation::with('user')->where('inventory_bagno', $bagno)->where('status_id', '2')->first();

        //PatDemo
        $uri = env('PAT_DEMO'). $request->epsdno;
        $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

        $response = $client->request('GET', $uri);

        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getBody(), true);

        $patdemo = $content['data'];

        $vitaltemp  = !empty($record->temperature) ? $record->temperature : ($patdemo['temp'] ?? '__');
        $vitalsysto = !empty($record->systo) ? $record->systo : ($patdemo['sysBP'] ?? '__');
        $vitaldysto = !empty($record->diasto) ? $record->diasto : ($patdemo['diasBP'] ?? '');
        $vitalpulse = !empty($record->pulserate) ? $record->pulserate : ($patdemo['pulRate'] ?? '__');
        $vitalspo   = !empty($record->spo) ? $record->spo : ($patdemo['spO'] ?? '__');

        //Inventory
        $inventory = BloodInventory::where('bagno', $request->input('bagno'))->where('episodeno', $request->epsdno)->first();


        if($record){
            if($inventory->transfuse_stop_at != null && $record->created_at != null){

                $transfuseStopAt = Carbon::parse($inventory->transfuse_stop_at);
                $createdAt = Carbon::parse($record->created_at);
                
                if ($createdAt->diffInHours($transfuseStopAt) <= 24) {
                    $onset = 'immediate';
                } else {
                    $onset = 'delayed';
                }
    
            }else{
                $onset = "immediate";
            }
        }else{
            $onset = "immediate";
        }
        

        


        return view('iblood.reaction.report.subviews.report', compact(
            'url', 
            'bagno', 
            'record', 
            'patdemo',  
            'typeadverseevent', 
            'outcomeadverseevent', 
            'relevantinvestigation', 
            'vitaltemp', 
            'vitalsysto', 
            'vitaldysto', 
            'vitalpulse', 
            'vitalspo',
            'onset',
            'inventory',
            'relevanthistory',
            'procedure',
            'component',
            'episode',
        ));
    }

    public function indexAdrWorklist(Request $request) 
    {
        $explode = explode('?', $request->getRequestUri());
        $url = $explode[1];

        // Clear all existing data in AdrList
        AdrList::truncate();

        // Fetch data from external API
        $uri = env('ADV_EVENT');
        $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

        $response = $client->request('GET', $uri);

        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getBody(), true);

        $list = $content['data'];

        // Insert new records into AdrList
        foreach ($list as $record) {
            $storerecord                 = new AdrList();
            $storerecord->adr_id         = $record['data'] ?? null;
            $storerecord->episodeno      = $record['episodeNo'] ?? null;
            $storerecord->drugname       = $record['itemDesc'] ?? null;

            // Save reported_at
            $storerecord->reported_at    = isset($record['advsDateReported']) 
                ? Carbon::createFromFormat('d/m/Y', $record['advsDateReported'])->format('Y-m-d H:i:s') 
                : null;

            // Save onset_at (combining advsOnSetDate and advsTimeReported)
            if (!empty($record['advsOnSetDate']) && !empty($record['advsTimeReported'])) {
                $datetime = $record['advsOnSetDate'] . ' ' . $record['advsTimeReported'];
                $storerecord->onset_at = Carbon::createFromFormat('d/m/Y H:i:s', $datetime)->format('Y-m-d H:i:s');
            } elseif (!empty($record['advsOnSetDate'])) {
                $storerecord->onset_at = Carbon::createFromFormat('d/m/Y', $record['advsOnSetDate'])->format('Y-m-d H:i:s');
            } else {
                $storerecord->onset_at = null;
            }

            $storerecord->severity       = $record['severityDesc'] ?? null;
            $storerecord->description    = $record['Desc'] ?? null;
            $storerecord->errordesc      = $record['advsEnterInErrorReasonDesc'] ?? null;
            $storerecord->created_at     = Carbon::now();
            $storerecord->save();

            // Handle AdrBridge insertion
            $adrId = $record['data'] ?? null;
            if ($adrId && !AdrBridge::where('adr_id', $adrId)->exists()) {
                $storebridge             = new AdrBridge();
                $storebridge->adr_id     = $adrId;
                $storebridge->created_at = Carbon::now();
                $storebridge->status_id  = 1;
                $storebridge->save();
            }
        }

        return view('ireporting.adr.index', compact('url'));
    }


    public function getAdrWorklistSuspect(Request $request)
    {
        // $report = AdrList::with(['patientinfo.patient']);
        $report = AdrBridge::with(['adrlist.patientinfo.patient'])->where('status_id', 1);

        // dd($report->get());

        // $report = AdrReport::with([
        //     'descriptions',
        //     'susdrugs' => function ($query) use ($request) {
        //         $query->where('status_id', 1);
        //     },
        //     'concodrugs' => function ($query) use ($request) {
        //         $query->where('status_id', 1);
        //     },
        //     'createdby:id,name', 
        //     'updatedby:id,name',
        //     'patientinfo.patient'
        // ])->where('status_id', 1 )->orderBy('id', 'desc');

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $report->whereBetween('created_at', [$startDate, $endDate]);
        }

        return response()->json(['data' => $report->get()]);
    }

    public function getAdrWorklistConfirm(Request $request)
    {
        $report = AdrReport::with([
            'descriptions',
            'susdrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'concodrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'createdby:id,name', 
            'updatedby:id,name',
            'patientinfo.patient'
        ])->where('status_id', 2 )->orderBy('id', 'desc');

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $report->whereBetween('created_at', [$startDate, $endDate]);
        }

        return response()->json(['data' => $report->get()]);
    }

    public function getAdrWorklistFalse(Request $request)
    {
        $report = AdrReport::with([
            'descriptions',
            'susdrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'concodrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'createdby:id,name', 
            'updatedby:id,name',
            'patientinfo.patient'
        ])->where('status_id', 3 )->orderBy('id', 'desc');

        // Filter by Date Range
        if ($request->has('dateRange')) {
            $dateRange = explode(' - ', $request->dateRange);
            $startDate = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();
            $report->whereBetween('created_at', [$startDate, $endDate]);
        }

        return response()->json(['data' => $report->get()]);
    }

    public function AdrReportConfirm(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        $report = AdrReport::with([
            'descriptions',
            'susdrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'concodrugs' => function ($query) use ($request) {
                $query->where('status_id', 3);
            },
            'createdby', 
            'updatedby',
        ])->where('episodeno', $request->epsdno)->where('adr_id', $request->adrid)->whereIn('status_id', [2, 3])->orderBy('id', 'desc')->first();

        //PatDemo
        $uri = env('PAT_DEMO'). $request->epsdno;
        $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

        $response = $client->request('GET', $uri);

        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getBody(), true);

        $patdemo = $content['data'];

        if (isset($report) && isset($report->createdBy)) {
            $email = $report->createdBy->username . "@ijn.com.my";
            $detail = Sso::where('email', $email)->select('mail', 'position')->first();
        } else {
            $detail = null;
        }

        return view('adr.report.subviews.report', compact(
            'url', 
            'report',
            'patdemo',
            'detail'
        ));
    }

    public function AdrReportSuspect(Request $request)
    {
        // dd($request->all());
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        $report = AdrReport::with([
            'descriptions',
            'susdrugs' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'concodrugs' => function ($query) use ($request) {
                $query->where('status_id', 1);
            },
            'createdby', 
            'updatedby',
        ])->where('episodeno', $request->epsdno)->where('adr_id', $request->adrid)->where('status_id', 1 )->orderBy('id', 'desc')->first();

        //PatDemo
        $uri = env('PAT_DEMO'). $request->epsdno;
        $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

        $response = $client->request('GET', $uri);

        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getBody(), true);

        $patdemo = $content['data'];

        if (isset($report) && isset($report->createdBy)) {
            $email = $report->createdBy->username . "@ijn.com.my";
            $detail = Sso::where('email', $email)->select('mail', 'position')->first();
        } else {
            $detail = null;
        }

        return view('adr.report.subviews.report', compact(
            'url', 
            'report',
            'patdemo',
            'detail'
        ));
    }

    public function getPatientInfo(Request $request)
    {
        $info = PatientInformation::with('patient')->where('episodenumber', $request->episodeno)->first();
        // IP0403242

        $response = response()->json(
            [
              'status'  => 'success',
              'data'    => $info
            ], 200
        );

        return $response;

    }

    //begin medshelf
    public function indexMedShelf(Request $request)
    {
        $explode = explode('?', $request->getRequestUri());

        $url = $explode[1];

        return view('ireporting.medshelf.index', compact('url'));
    }

    public function apiGetDataMedShelf(Request $request)
    {
        try
        {

            $dateRange  = explode(' - ', $request->dateRange);
            $startDate  = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate    = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();

            $hasDs = MedShelf::whereBetween('created_at', [$startDate, $endDate])->orderBy('created_at', 'desc')
                    ->get();

            $data = [];

            foreach($hasDs as $ds)
            {
                $temp = [];

                $userssoid = MedShelfUser::select('user_sso_id')->where('id', $ds['user_id'])->first();

                $username = MedShelfUserSSO::select('name')->where('id', $userssoid->user_sso_id)->first();
               
                $getdrugcode = explode('  ',$ds['param_one']);
                $getdrugname = explode('  ',$ds['param_two']);

                if(isset($getdrugcode[7])){
                    $drugnamecodeone = $getdrugcode[0] . '(' . $getdrugcode[7] . ')';
                }
                else if(isset($getdrugcode[4])){
                    $drugnamecodeone = $getdrugcode[0] . '(' . $getdrugcode[4] . ')';
                }
                else{
                    $drugnamecodeone = '-';
                }

                if(isset($getdrugname[7])){
                    $drugnamecodetwo = $getdrugname[0] . '(' . $getdrugname[7] . ')';
                }
                else if(isset($getdrugname[4])){
                    $drugnamecodetwo = $getdrugname[0] . '(' . $getdrugname[4] . ')';
                }
                else{
                    $drugnamecodetwo = '-';
                }

                $temp['drugcode']            = $drugnamecodeone;
                $temp['drugname']            = $drugnamecodetwo;
                $temp['scanby']              = $username->name;
                $temp['scandate']            = Carbon::parse($ds['created_at'])->format('Y-m-d');
                $temp['status']              = $ds['status'];

                array_push($data, $temp);
            }
            
            $response = response()->json(
                [
                  'status'  => 'success',
                  'data'    => $data
                ], 200
            );

            return $response;
        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $response = response()->json(
                [
                    'status'  => 'failed',
                    'message' => 'Internal error happened. Try again'
                ], 200
            );

            return $response;
        }
    }
    //end medshelf

    public function getSingleIbloodInventory(Request $request)
    {
        $info = BloodInventory::where('episodeno', $request->episodeno)->where('bagno', $request->bagno)->where('labno', $request->labno)->first();

        $response = response()->json(
            [
              'status'  => 'success',
              'data'    => $info
            ], 200
        );

        return $response;

    }

    public function updateIbloodInv(Request $request)
    {
        try
        {

            $updateinv = BloodInventory::where('episodeno', $request->updateep)->where('bagno', $request->updatebag)->where('labno', $request->updatelab)->first();
            $updateinv->product         = $request->updateproduct;
            $updateinv->expiry_date     = $request->updateexpiry;
            $updateinv->transfer_to     = $request->updatetransferloc;
            $updateinv->save();

            $response = response()->json(
                [
                  'status'  => 'success',
                  'message'    => "Successfully Update!"
                ], 200
            );
    
            return $response;

        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $response = response()->json(
                [
                    'status'  => 'failed',
                    'message' => 'Internal error happened. Try again'
                ], 200
            );

            return $response;
        }
    }

    // start consent
    public function indexConsent(Request $request) 
    {
        $explode = explode('?', $request->getRequestUri());
        $url = $explode[1];

        

        return view('ireporting.consent.index', compact('url'));
    }

    public function apiGetDataConsent(Request $request) 
    {
        try
        {
            $dateRange  = explode(' - ', $request->dateRange);
            $startDate  = Carbon::createFromFormat('d/m/Y', $dateRange[0])->startOfDay();
            $endDate    = Carbon::createFromFormat('d/m/Y', $dateRange[1])->endOfDay();

            $hasConsent = ConsentList::whereBetween('created_at', [$startDate, $endDate])
                        ->with('createdby', function($q){
                            $q->select('id', 'name');
                        })
                        ->with('updatedby', function($q){
                            $q->select('id', 'name');
                        })
                        ->where('status_id', 2)
                        ->orderBy('created_at', 'desc')
                        ->get();

            $data = [];

            foreach($hasConsent as $consent)
            {
                $temp = [];
                
                $getPatInfo = PatientInformation::where('status_id', 2)
                                ->select('id', 'patient_id', 'episodenumber', 'epsiodedate')
                                ->where('episodenumber', $consent['episodeno'])
                                ->with('patient', function($q){
                                    $q->select('id', 'mrn', 'name');
                                })
                                ->first();

                $temp['patname']             = $getPatInfo['patient']['name'];
                $temp['mrn']                 = $getPatInfo['patient']['mrn'];
                $temp['episodeno']           = $consent['episodeno'];
                $temp['consentname']         = $consent['formname'];
                $temp['createdby']           = $consent['createdby']['name'];
                $temp['createddate']         = Carbon::parse($consent['created_at'])->format('Y-m-d');
                $temp['createdtime']         = Carbon::parse($consent['created_at'])->format('H:i');

                array_push($data, $temp);
            }
            
            $response = response()->json(
                [
                  'status'  => 'success',
                  'data'    => $data
                ], 200
            );

            return $response;
        }
        catch(\Exception $e)
        {
            Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $response = response()->json(
                [
                    'status'  => 'failed',
                    'message' => 'Internal error happened. Try again'
                ], 200
            );

            return $response;
        }
    }
    // end consent
}
