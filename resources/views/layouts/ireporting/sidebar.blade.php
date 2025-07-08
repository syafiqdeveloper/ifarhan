{{-- <!-- start nav ida -->
	@include('layouts.subviewssidebar.navida')
<!-- end nav ida --> --}}

@php
    $usrGrp = request()->get('usrGrp');

	$inursingRoutes = config('dynamicroutes.inursing_routes');
@endphp

<!-- iMilk -->
@if(in_array($usrGrp, ["Administrator", "Doctors", "WardNurse", "WardNursePrivate", "WardClerk", "WardManagerOrMentor", "OPDNurse"]))
	<div class="row {{ request()->routeIs('report.imilk.index') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.imilk.index') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.imilk.index') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.imilk.index') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.imilk.index') }}?{{$url}}" style="margin-bottom: 10px;">iMilk</a>
		</div>
	</div>
@endif

<!-- iBlood Dropdown -->
@if(in_array($usrGrp, ["Administrator", "Doctors", "WardNurse", "WardNursePrivate", "WardClerk", "WardManagerOrMentor", "OPDNurse", "LABManager", "LABMLT", "LABTemp", "LABClerk","EMY","EMYDoctors","ICLNurse","OTNurse", "QualityManagement"]))
	<div class="row {{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success" href="#" data-bs-toggle="collapse" data-bs-target="#ibloodSubmenu" aria-expanded="{{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? 'true' : 'false' }}" aria-controls="ibloodSubmenu">
				<i id="ibloodArrow" class="fas fa-angle-right fs-3 dropdown-toggle-icon {{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? 'rotate-90' : '' }}" 
					style="float: right; margin-bottom: 10px; color: {{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? '#fff' : '#14787c' }}; transition: transform 0.3s;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? 'text-white' : 'text-dark' }}" 
				data-bs-toggle="collapse" data-bs-target="#ibloodSubmenu" href="#" style="margin-bottom: 10px;">iBlood</a>
		</div>
	</div>
@endif
<!-- Submenu for iBlood -->
<div class="collapse {{ request()->routeIs('report.iblood.index') || request()->routeIs('report.iblood.atr.index') ? 'show' : '' }}" id="ibloodSubmenu">
	<div class="row" style="padding-left: 30px; margin-top: 10px;">
		@if(in_array($usrGrp, ["Administrator", "Doctors", "WardNurse", "WardNursePrivate", "WardClerk", "WardManagerOrMentor", "OPDNurse", "LABManager", "LABMLT", "LABTemp", "LABClerk","EMY","EMYDoctors","ICLNurse","OTNurse"]))
			<div class="col-12" style="padding-bottom: 8px;">
				<a class="text-hover-success {{ request()->routeIs('report.iblood.index') ? 'text-teal' : 'text-dark' }}" 
				href="{{ route('report.iblood.index') }}?{{$url}}" style="margin-bottom: 10px;">History</a>
			</div>
		@endif
		@if(in_array($usrGrp, ["Administrator", "Doctors", "WardNurse", "WardNursePrivate", "WardClerk", "WardManagerOrMentor", "OPDNurse", "LABManager", "LABMLT", "LABTemp", "LABClerk","EMY","EMYDoctors","ICLNurse","OTNurse", "QualityManagement"]))
			<div class="col-12">
				<a class="text-hover-success {{ request()->routeIs('report.iblood.atr.index') ? 'text-teal' : 'text-dark' }}" 
				href="{{ route('report.iblood.atr.index') }}?{{$url}}" style="margin-bottom: 10px;">ATR Worklist</a>
			</div>
		@endif
	</div>
</div>

<!-- IDA Dropdown -->
{{-- @if(in_array($usrGrp, ["Administrator"]))
	<div class="row {{ request()->routeIs('report.ida.preadmission.index') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success" href="#" data-bs-toggle="collapse" data-bs-target="#idaSubmenu" aria-expanded="{{ request()->routeIs('report.ida.preadmission.index') ? 'true' : 'false' }}" aria-controls="idaSubmenu">
				<i id="idaArrow" class="fas fa-angle-right fs-3 dropdown-toggle-icon {{ request()->routeIs('report.ida.preadmission.index') ? 'rotate-90' : '' }}" 
					style="float: right; margin-bottom: 10px; color: {{ request()->routeIs('report.ida.preadmission.index') ? '#fff' : '#14787c' }}; transition: transform 0.3s;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.ida.preadmission.index') ? 'text-white' : 'text-dark' }}" 
				data-bs-toggle="collapse" data-bs-target="#idaSubmenu" href="#" style="margin-bottom: 10px;">IDA</a>
		</div>
	</div>
@endif --}}
<!-- Submenu for IDA -->
{{-- <div class="collapse {{ request()->routeIs('report.ida.preadmission.index') ? 'show' : '' }}" id="idaSubmenu">
   <div class="row" style="padding-left: 30px; margin-top: 10px;">
	   @if(in_array($usrGrp, ["Administrator"]))
		   <div class="col-12" style="padding-bottom: 8px;">
			   <a class="text-hover-success {{ request()->routeIs('report.ida.preadmission.index') ? 'text-teal' : 'text-dark' }}" 
			   href="{{ route('report.ida.preadmission.index') }}?{{$url}}" style="margin-bottom: 10px;">Pre-Admission</a>
		   </div>
	   @endif --}}
	   {{-- @if(in_array($usrGrp, ["Administrator", "LABManager", "LABMLT", "LABTemp", "LABClerk", "QualityManagement"]))
		   <div class="col-12">
			   <a class="text-hover-success {{ request()->routeIs('report.iblood.atr.index') ? 'text-teal' : 'text-dark' }}" 
			   href="{{ route('report.iblood.atr.index') }}?{{$url}}" style="margin-bottom: 10px;">Worklist</a>
		   </div>
	   @endif --}}
   {{-- </div>
</div> --}}
@if(in_array($usrGrp, ["Administrator" , "MROffice"]))
	<div class="row {{ request()->routeIs('report.dischargesummary') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.dischargesummary') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.dischargesummary') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.dischargesummary') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.dischargesummary') }}?{{$url}}" style="margin-bottom: 10px;">Discharge Summary</a>
		</div>
	</div>
	<div class="row {{ request()->routeIs('report.dischargesummary.unfinalized') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.dischargesummary.unfinalized') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.dischargesummary.unfinalized') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.dischargesummary.unfinalized') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.dischargesummary.unfinalized') }}?{{$url}}" style="margin-bottom: 10px;">Pending Discharge Summary: Unfinalized Patient Records</a>
		</div>
	</div>
@endif

@if(in_array($usrGrp, ["Administrator"]))
	<div class="row {{ request()->routeIs('report.medshelf') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.medshelf') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.medshelf') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.medshelf') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.medshelf') }}?{{$url}}" style="margin-bottom: 10px;">MedShelf</a>
		</div>
	</div>
@endif

@if(in_array($usrGrp, ["Administrator", "Pharmacist", "PharmacyAssistant", "WardNurse", "WardNursePrivate", "WardClerk", "WardManagerOrMentor", "OPDNurse", "ICLNurse","OTNurse", "QualityManagement", "Doctors", "EMYDoctors" ]))
	<div class="row {{ request()->routeIs('report.adr.index') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.adr.index') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.adr.index') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.adr.index') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.adr.index') }}?{{$url}}" style="margin-bottom: 10px;">ADR Worklist</a>
		</div>
	</div>
@endif

@if(in_array($usrGrp, ["Administrator", "MROffice", "MRManager", "MRExecutive", "MRCashier"]))
	<div class="row {{ request()->routeIs('report.consent.index') ? 'bg-teal text-white' : '' }}" 
		style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
		<div class="col-2 mt-2">
			<a class="text-hover-success {{ request()->routeIs('report.consent.index') ? 'display-none' : 'display-block' }}" href="#" id="expandhr" style="margin-bottom: 10px; display: block;">
				<i class="fas fa-angle-right fs-3 {{ request()->routeIs('report.consent.index') ? 'color-white' : 'color-teal' }}" 
					style="float: right; margin-bottom: 10px;"></i>
			</a>
		</div>
		<div class="col-10 mt-2" style="padding-left: 0px;">
			<a class="text-hover-success {{ request()->routeIs('report.consent.index') ? 'text-white' : 'text-dark' }}" 
				href="{{ route('report.consent.index') }}?{{$url}}" style="margin-bottom: 10px;">Consent Listing</a>
		</div>
	</div>
@endif

<!--iNursing-->
@if (in_array(Request::getHost(), ['127.0.0.1', 'uat-ireporting.ijn.com.my']))
<div class="row {{ in_array(request()->route()->getName(), $inursingRoutes) ? 'bg-teal text-white' : '' }}" 
	style="padding: 0.5rem; margin: auto; border-bottom: solid 1px #918f8f;">
	<div class="col-2 mt-2">
		<a class="text-hover-success" href="#" data-bs-toggle="collapse" data-bs-target="#iNurSubmenu" aria-expanded="{{ in_array(request()->route()->getName(), $inursingRoutes) ? 'true' : 'false' }}" aria-controls="iNurSubmenu">
			<i id="iNurArrow" class="fas fa-angle-right fs-3 dropdown-toggle-icon {{ in_array(request()->route()->getName(), $inursingRoutes) ? 'rotate-90' : '' }}" 
				style="float: right; margin-bottom: 10px; color: {{ in_array(request()->route()->getName(), $inursingRoutes) ? '#fff' : '#14787c' }}; transition: transform 0.3s;"></i>
		</a>
	</div>
	<div class="col-10 mt-2" style="padding-left: 0px;">
		<a class="text-hover-success {{ in_array(request()->route()->getName(), $inursingRoutes) ? 'text-white' : 'text-dark' }}" 
			data-bs-toggle="collapse" data-bs-target="#iNurSubmenu" href="#" style="margin-bottom: 10px;">iNursing</a>
	</div>
</div>
<!-- Submenu for iNursing -->
<div class="collapse {{ in_array(request()->route()->getName(), $inursingRoutes) ? 'show' : '' }}" id="iNurSubmenu">
	<div class="row" style="padding-left: 30px;">
		@include('layouts.ireporting.inursing.subviewssidebar.navinurwardorientation')
		@include('layouts.ireporting.inursing.subviewssidebar.navinurcareforms')
		@include('layouts.ireporting.inursing.subviewssidebar.navinurhomeinotropesforms')
		@include('layouts.ireporting.inursing.subviewssidebar.navinurdischargeforms')
	</div>
</div>
<!-- Submenu for iNursing -->
@endif
<!--iNursing-->
