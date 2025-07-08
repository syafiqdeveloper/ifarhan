<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;

//setting
use App\Http\Controllers\SettingController;

//human milk
use App\Http\Controllers\HmilkMainController;
use App\Http\Controllers\HmilkStorageController;
use App\Http\Controllers\HmilkAdministerController;

//iblood
use App\Http\Controllers\BloodInventoryController;
use App\Http\Controllers\BloodTransfusionController;
use App\Http\Controllers\BloodReactionController;

//ireporting
use App\Http\Controllers\iReportingMainController;

//ADR
use App\Http\Controllers\AdrController;

//CLR
use App\Http\Controllers\ClrController;

//bedmanagement
use App\Http\Controllers\BedManagementController;
use App\Http\Controllers\iNursingController;
//user access controller
use App\Http\Controllers\UserAccessRoleController;


Route::group(['middleware' => ['authsystem']], function() {

    Route::group(['prefix' => 'data'], function () {
        Route::get('/patient-info', [MainController::class, 'apiGetPatientInfo'])->name('main.patientinfo');
    });
	
    //Human Milk
    Route::group(['prefix' => 'hmilk'], function () {
        Route::get('/', [HmilkMainController::class, 'index'])->name('hmilk.index');

        Route::group(['prefix' => 'main'], function () {
            Route::get('/', [HmilkMainController::class, 'index'])->name('hmilk.main.index');
            Route::get('/list', [HmilkMainController::class, 'list'])->name('hmilk.main.list');
        });

        Route::group(['prefix' => 'storage'], function () {
            Route::get('/', [HmilkStorageController::class, 'index'])->name('hmilk.storage.index');
            Route::get('/check', [HmilkStorageController::class, 'check'])->name('hmilk.storage.check');
            Route::get('/list', [HmilkStorageController::class, 'batchList'])->name('hmilk.storage.list');
            Route::post('/store', [HmilkStorageController::class, 'store'])->name('hmilk.storage.store');
            Route::post('/store-detail', [HmilkStorageController::class, 'storeDetail'])->name('hmilk.storage.store.detail');
            Route::post('/discard-detail', [HmilkStorageController::class, 'discardDetail'])->name('hmilk.storage.discard.detail');
            Route::post('/discard', [HmilkStorageController::class, 'discard'])->name('hmilk.storage.discard');
            Route::post('/detail', [HmilkStorageController::class, 'batchDetail'])->name('hmilk.storage.detail');
            Route::post('/update-location', [HmilkStorageController::class, 'updateLocation'])->name('hmilk.storage.updatelocation');
            Route::post('/reprint-label', [HmilkStorageController::class, 'reprintLabel'])->name('hmilk.storage.reprintLabel');
            Route::post('/transfer-ward', [HmilkStorageController::class, 'transferWard'])->name('hmilk.storage.transferward');
        });

        Route::group(['prefix' => 'administer'], function () {
            Route::get('/', [HmilkAdministerController::class, 'index'])->name('hmilk.administer.index');
            Route::post('/detail', [HmilkAdministerController::class, 'detail'])->name('hmilk.administer.detail');
            Route::post('/submit', [HmilkAdministerController::class, 'submit'])->name('hmilk.administer.submit');

            Route::group(['prefix' => 'reheat'], function () {
                Route::get('/', [HmilkAdministerController::class, 'reheatIndex'])->name('hmilk.administer.reheat.index');
                Route::post('/detail', [HmilkAdministerController::class, 'reheatDetail'])->name('hmilk.administer.reheat.detail');
                Route::post('/update', [HmilkAdministerController::class, 'reheatUpdate'])->name('hmilk.administer.reheat.update');
                Route::post('/check', [HmilkAdministerController::class, 'reheatCheck'])->name('hmilk.administer.reheat.check');

                Route::group(['prefix' => 'caregiver'], function () {
                    Route::post('/update', [HmilkAdministerController::class, 'cgReheatUpdate'])->name('hmilk.administer.reheat.caregiver.update');
                    Route::post('/check', [HmilkAdministerController::class, 'cgReheatCheck'])->name('hmilk.administer.reheat.caregiver.check');
                });
            });
        });
    });

    //iBlood
    Route::group(['prefix' => 'blood'], function () {
        Route::group(['prefix' => 'inventory'], function () {
            Route::get('/', [BloodInventoryController::class, 'index'])->name('blood.inventory.index');
            Route::get('/list', [BloodInventoryController::class, 'bloodList'])->name('blood.inventory.list');
            Route::get('/ward-list', [BloodInventoryController::class, 'wardLocationList'])->name('blood.inventory.wardlocationlist');
            Route::post('/get-transferto', [BloodInventoryController::class, 'getTransferTo'])->name('blood.inventory.gettransferto');
            Route::post('/verify-lab', [BloodInventoryController::class, 'verifyLab'])->name('blood.inventory.verifylab');
            Route::post('/store', [BloodInventoryController::class, 'store'])->name('blood.inventory.store');
            Route::post('/suspend', [BloodInventoryController::class, 'suspend'])->name('blood.inventory.suspend');
            Route::post('/add-reaction', [BloodInventoryController::class, 'addReaction'])->name('blood.inventory.addreaction');
            Route::post('/update-location', [BloodInventoryController::class, 'updateLocation'])->name('blood.inventory.updatelocation');
            Route::post('/store-blood', [BloodInventoryController::class, 'storeBlood'])->name('blood.inventory.storeblood');
            Route::post('/view-reaction', [BloodInventoryController::class, 'reactionList'])->name('blood.inventory.reactionlist');
            Route::post('/receive-transferred', [BloodInventoryController::class, 'receiveTransferred'])->name('blood.inventory.receivetransferred');
        });
        Route::group(['prefix' => 'transfusion'], function () {
            Route::get('/', [BloodTransfusionController::class, 'index'])->name('blood.transfusion.index');
            Route::post('/detail', [BloodTransfusionController::class, 'detail'])->name('blood.transfusion.detail');
            Route::post('/submit', [BloodTransfusionController::class, 'submit'])->name('blood.transfusion.submit');
        });
        Route::group(['prefix' => 'reaction'], function () {
            Route::get('/', [BloodReactionController::class, 'index'])->name('blood.reaction.index');

            Route::group(['prefix' => 'signandsymptoms'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeSignSymptoms'])->name('blood.reaction.signandsymptoms.store');
                Route::get('/getsignsymptoms', [BloodReactionController::class, 'getSignSymptoms'])->name('blood.reaction.signandsymptoms.get');
                
            });
            Route::group(['prefix' => 'typeadverseevent'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeTypeAdverseEvent'])->name('blood.reaction.typeadverseevent.store');
                
            });
            Route::group(['prefix' => 'outcomeadverseevent'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeOutcomeAdverseEvent'])->name('blood.reaction.outcomeadverseevent.store');
                
            });
            Route::group(['prefix' => 'relevantinvestigation'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeRelevantInvestigation'])->name('blood.reaction.relevantinvestigation.store');                
            });

            Route::group(['prefix' => 'relevanthistory'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeRelevantHistory'])->name('blood.reaction.relevanthistory.store');                
            });

            Route::group(['prefix' => 'detailprocedure'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeDetailProcedure'])->name('blood.reaction.detailprocedure.store');                
            });

            Route::group(['prefix' => 'bloodcomponent'], function () {
                Route::post('/store', [BloodReactionController::class, 'storeBloodComponent'])->name('blood.reaction.bloodcomponent.store');                
            });

            Route::group(['prefix' => 'report'], function () {
                Route::get('/generate', [BloodReactionController::class, 'genReport'])->name('blood.reaction.report.generate');   
                Route::post('/finalize', [BloodReactionController::class, 'finalize'])->name('blood.reaction.report.finalize');
                Route::post('/false', [BloodReactionController::class, 'falseReport'])->name('blood.reaction.report.false');                                                          
            });
        });
    });

    //Administrator
    Route::group(['prefix' => 'administrator'], function () {
        Route::group(['prefix' => 'user-access-role'], function () {
            Route::get('/', [UserAccessRoleController::class, 'index'])->name('administrator.useraccessrole');
            Route::get('/data', [UserAccessRoleController::class, 'apiGetDataUserAccessRole'])->name('administrator.useraccessrole.role');
            Route::post('/store', [UserAccessRoleController::class, 'apiPostAddUserAccessRole'])->name('administrator.useraccessrole.store');
            Route::post('/update', [UserAccessRoleController::class, 'apiGetUpdateUserAccessRole'])->name('administrator.useraccessrole.update');
        });
    });

    //Setting
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [SettingController::class, 'index'])->name('setting');
        Route::get('/list', [SettingController::class, 'index'])->name('setting.list');
    });

    //iReporting
    Route::group(['prefix' => 'ireporting'], function () {
        Route::group(['prefix' => 'imilk'], function () {
            Route::get('/', [iReportingMainController::class, 'indexImilk'])->name('report.imilk.index');
            Route::get('/getimilkinventory', [iReportingMainController::class, 'getImilkInventory'])->name('report.imilk.getinventory');
            Route::get('/getimilkcompliance', [iReportingMainController::class, 'getImilkCompliance'])->name('report.imilk.getcompliance');
        });
        Route::group(['prefix' => 'iblood'], function () {
            Route::get('/', [iReportingMainController::class, 'indexIblood'])->name('report.iblood.index');
            Route::get('/getibloodinventory', [iReportingMainController::class, 'getIbloodInventory'])->name('report.iblood.getinventory');
            Route::post('/getlocationdetails', [iReportingMainController::class, 'getIbloodLocationDetails'])->name('report.iblood.getlocationdetails');
            Route::post('/getsingleinventory', [iReportingMainController::class, 'getSingleIbloodInventory'])->name('report.iblood.getsingleinventory');
            Route::post('/updateibloodinv', [iReportingMainController::class, 'updateIbloodInv'])->name('report.iblood.updateibloodinv');

            Route::group(['prefix' => 'atr'], function () {
                Route::get('/', [iReportingMainController::class, 'indexIbloodAtr'])->name('report.iblood.atr.index');
                Route::get('/getworklist', [iReportingMainController::class, 'getIBloodAtrWorklist'])->name('report.iblood.atr.getworklist');
                Route::get('/getworklistconfirm', [iReportingMainController::class, 'getIBloodAtrWorklistConfirm'])->name('report.iblood.atr.getworklistconfirm');
                Route::get('/getworklistfalse', [iReportingMainController::class, 'getIBloodAtrWorklistFalse'])->name('report.iblood.atr.getworklistfalse');
                Route::get('/generateconfirm', [iReportingMainController::class, 'genReportConfirm'])->name('report.iblood.atr.generateconfirm');   
            });
        });

        Route::group(['prefix' => 'ida'], function () {
            Route::group(['prefix' => 'preadmission'], function () {
                Route::get('/', [iReportingMainController::class, 'indexPreAdmission'])->name('report.ida.preadmission.index');
                Route::get('/getpreadmissioninventory', [iReportingMainController::class, 'getPreAdmissionInventory'])->name('report.ida.preadmission.getinventory');
            });
        });

        Route::group(['prefix' => 'discharge-summary'], function () {
            Route::get('/', [iReportingMainController::class, 'indexDischargeSummary'])->name('report.dischargesummary');
            Route::get('/list', [iReportingMainController::class, 'apiGetDataDischargeSummary'])->name('report.dischargesummary.list');
        });

        Route::group(['prefix' => 'pending-discharge-summary'], function () {
            Route::get('/', [iReportingMainController::class, 'indexDischargeSummaryUnfinalized'])->name('report.dischargesummary.unfinalized');
            Route::get('/list', [iReportingMainController::class, 'apiGetDataDischargeSummaryUnfinalized'])->name('report.dischargesummary.unfinalized.list');
        });

        Route::group(['prefix' => 'adr'], function () {
            Route::get('/', [iReportingMainController::class, 'indexAdrWorklist'])->name('report.adr.index');
            Route::get('/getworklistsuspect', [iReportingMainController::class, 'getAdrWorklistSuspect'])->name('report.adr.getworklistsuspect');
            Route::get('/getworklistconfirm', [iReportingMainController::class, 'getAdrWorklistConfirm'])->name('report.adr.getworklistconfirm');
            Route::get('/getworklistfalse', [iReportingMainController::class, 'getAdrWorklistFalse'])->name('report.adr.getworklistfalse');
            Route::get('/generateconfirm', [iReportingMainController::class, 'AdrReportConfirm'])->name('report.adr.generateconfirm');
            Route::get('/generatesuspect', [iReportingMainController::class, 'AdrReportSuspect'])->name('report.adr.generatesuspect'); 
            Route::post('/getpatientinfo', [iReportingMainController::class, 'getPatientInfo'])->name('report.adr.getpatientinfo');     
        });

        Route::group(['prefix' => 'medshelf'], function () {
            Route::get('/', [iReportingMainController::class, 'indexMedShelf'])->name('report.medshelf');
            Route::get('/list', [iReportingMainController::class, 'apiGetDataMEdShelf'])->name('report.medshelf.list');
        });

        Route::group(['prefix' => 'consent'], function () {
            Route::get('/', [iReportingMainController::class, 'indexConsent'])->name('report.consent.index');
            Route::get('/list', [iReportingMainController::class, 'apiGetDataConsent'])->name('report.consent.list');
        });

        //iNursing
        Route::group(['prefix' => 'inursing'], function () {
            Route::group(['prefix' => 'wardorientation'], function () {
                Route::get('/', [iNursingController::class, 'indexWardOrientation'])->name('report.inursing.wardorientation.index');

                Route::group(['prefix' => 'orientation'], function () {
                    Route::get('/list', [iNursingController::class, 'getDataWOOrientation'])->name('report.inursing.wardorientation.orientation.getdatawoorientation');
                });

                Route::group(['prefix' => 'transfer'], function () {
                    Route::get('/list', [iNursingController::class, 'getDataWOTransfer'])->name('report.inursing.wardorientation.transfer.getdatawotransfer');
                });                              
            });

            Route::group(['prefix' => 'bedsidemobilityassmnt'], function () {
                Route::get('/', [iNursingController::class, 'indexBMAT'])->name('report.inursing.bedsidemobilityassmnt.index');
                Route::get('/list', [iNursingController::class, 'getDataBMAT'])->name('report.inursing.bedsidemobilityassmnt.getdatabmat');                              
            });

            Route::group(['prefix' => 'safetychecklist'], function () {
                Route::get('/', [iNursingController::class, 'indexSafetyChecklist'])->name('report.inursing.safetychecklist.index');
                Route::get('/list', [iNursingController::class, 'getDataSafetyChecklist'])->name('report.inursing.safetychecklist.getdatasafetychecklist');                              
            });

            Route::group(['prefix' => 'dysphagia'], function () {
                Route::get('/', [iNursingController::class, 'indexDysphagia'])->name('report.inursing.dysphagia.index');
                Route::get('/list', [iNursingController::class, 'getDataDysphagia'])->name('report.inursing.dysphagia.getdatadysphagia');                              
            });

            Route::group(['prefix' => 'peritonealchart'], function () {
                Route::get('/', [iNursingController::class, 'indexPeritonealDialysisChart'])->name('report.inursing.peritonealchart.index');
                Route::get('/list', [iNursingController::class, 'getDataPeritonealDialysisChart'])->name('report.inursing.peritonealchart.getdataperitonealchart');                              
            });

            Route::group(['prefix' => 'limbrestraint'], function () {
                Route::get('/', [iNursingController::class, 'indexLimbRestraint'])->name('report.inursing.limbrestraint.index');
                Route::get('/list', [iNursingController::class, 'getDataLimbRestraintAssmt'])->name('report.inursing.limbrestraint.getdataassmt');                              
            }); 

            Route::group(['prefix' => 'miscellaneous'], function () {
                Route::get('/', [iNursingController::class, 'indexMiscellaneous'])->name('report.inursing.miscellaneous.index');
                Route::get('/list', [iNursingController::class, 'getDataMiscellaneous'])->name('report.inursing.miscellaneous.getdatamiscellaneous');             
            });

            Route::group(['prefix' => 'patientassmtchecklist'], function () {
                Route::get('/', [iNursingController::class, 'indexPatientAssmtChecklist'])->name('report.inursing.patientassmtchecklist.index');
                Route::get('/list', [iNursingController::class, 'getDataPatientAssmtChecklist'])->name('report.inursing.patientassmtchecklist.getdatapatientassmtchecklist');                              
            });

            Route::group(['prefix' => 'homeassmtchecklist'], function () {
                Route::get('/', [iNursingController::class, 'indexHomeAssmtChecklist'])->name('report.inursing.homeassmtchecklist.index');
                Route::get('/list', [iNursingController::class, 'getDataHomeAssmtChecklist'])->name('report.inursing.homeassmtchecklist.getdatahomeassmtchecklist');                              
            });

            Route::group(['prefix' => 'dischargechecklist'], function () {
                Route::get('/', [iNursingController::class, 'indexDischargeChecklist'])->name('report.inursing.dischargechecklist.index');
                Route::get('/list', [iNursingController::class, 'getDataDischargeChecklist'])->name('report.inursing.dischargechecklist.getdatadischargechecklist');                              
            });

            Route::group(['prefix' => 'postdischarge'], function () {
                Route::get('/', [iNursingController::class, 'indexPostDischarge'])->name('report.inursing.postdischarge.index');
                Route::get('/list', [iNursingController::class, 'getDataPostDischarge'])->name('report.inursing.postdischarge.getdatapostdischarge');                              
            });
        });
    });

    //ADR
    Route::group(['prefix' => 'adr'], function () {
        Route::group(['prefix' => 'report'], function () {
            Route::get('/', [AdrController::class, 'index'])->name('adr.report.index');   
            Route::get('/generate', [AdrController::class, 'genReport'])->name('adr.report.generate');
            Route::get('/schedule-email', [AdrController::class, 'scheduleEmail'])->name('adr.report.scheduleemail');   
            Route::post('/save-finalize', [AdrController::class, 'saveFinalize'])->name('adr.report.savefinalize');
            Route::post('/save-false', [AdrController::class, 'saveFalse'])->name('adr.report.savefalse');
            Route::post('/save-record', [AdrController::class, 'saveRecord'])->name('adr.report.saverecord');
        });

    });

    //Critical Lab Result
    Route::group(['prefix' => 'critical-lab'], function () {
        Route::get('/', [ClrController::class, 'index'])->name('clr.index');  
        Route::post('/save-eventone', [ClrController::class, 'saveEventOne'])->name('clr.save.eventone');                             
        Route::post('/save-eventtwo', [ClrController::class, 'saveEventTwo'])->name('clr.save.eventtwo');                             
    });

    //Critical Lab Result
    Route::group(['prefix' => 'bed-management'], function () {
        Route::get('/', [BedManagementController::class, 'index'])->name('bm.index');
        Route::get('/getwardlist', [BedManagementController::class, 'wardList'])->name('bm.getwardlist'); 
        Route::get('/getpatientinfo', [BedManagementController::class, 'patientInfo'])->name('bm.getpatientinfo');
    });
});

Route::get('/ibed-schedulebackup', [BedManagementController::class, 'scheduleBackup'])->name('bm.scheduleBackup');
Route::get('/testgetcitizen', [BloodInventoryController::class, 'testgetpatientcitizen'])->name('bm.testgetpatientcitizen');

Route::get('/test', [HmilkMainController::class, 'test'])->name('hmilk.main.test');
Route::get('/testprint', [HmilkMainController::class, 'testprint'])->name('hmilk.test.print');

Route::get('/testpdfreport', function () {
    return view('iblood.reaction.report.subviews.report');
})->name('iblood.test.pdfreport');

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'iblood'], function () {
        Route::get('/supended-transfusion-list', [BloodInventoryController::class, 'apiGetSuspendedTransfusionList'])->name('api.iblood.apiGetSuspendedTransfusionList');
        Route::get('/supended-icca', [BloodInventoryController::class, 'apiSuspendICCA'])->name('api.iblood.apiSuspendICCA');                      
    });                           
});

Route::group(['prefix' => 'scheduler'], function () {
    Route::group(['prefix' => 'adr'], function () {
        Route::get('/suspected-email', [AdrController::class, 'suspectedEmail'])->name('scheduler.adr.suspectedemail');
    });                           
});
