var table = $('#reportdsunfinalized-table').DataTable({
    lengthMenu: [10, 20, 50, 100],
    dom       : 'Bfrtipl',
    scrollX   : "300px",
    buttons: [
        {
            extend: 'excel',
            title: 'Report - Pending Discharge Summary: Unfinalized Patient Records',
            className: 'btn-dark',
        },
    ],
    columns: [
        {
            "data": null,
            "render": function (data, type, row, meta) {
                return meta.row + 1;
            }
        },
        {
            "data": 'mrn',
            "render": function (data, type, row)  {
                return '<span>'+row.mrn+'</span>';
            }
        },
        {
            "data": 'name',
            "render": function (data, type, row)  {
                return '<span>'+row.name+'</span>';
            }
        },
        {
            "data": 'consultantname',
            "render": function (data, type, row)  {
                return '<span>'+row.consultantname+'</span>';
            }
        },
        {
            "data": 'episode',
            "render": function (data, type, row)  {
                return '<span>'+row.episode+'</span>';
            }
        },
        {
            "data": 'ward',
            "render": function (data, type, row)  {
                return '<span>'+row.ward+'</span>';
            }
        },
        {
            "data": 'dischargedate',
            "render": function (data, type, row)  {
                if(row.dischargedate != null)
                    return '<span>'+moment(row.dischargedate).format('DD/MM/YYYY')+'</span>';
                else
                    return '-';
            }
        },
        {
            "data": 'dischargedsummaryentry',
            "render": function (data, type, row)  {
                if(row.dischargedsummaryentry != null)
                    return '<span>'+moment(row.dischargedsummaryentry).format('DD/MM/YYYY')+'</span>';
                else
                    return '-';
            }
        },
        {
            "data": 'finalby',
            "render": function (data, type, row)  {
                if(row.finalby != null)
                    return '<span>'+row.finalby+'</span>';
                else
                    return '-';
            }
        },
        {
            "data": 'savedraftby',
            "render": function (data, type, row)  {
                if(row.savedraftby != null)
                    return '<span>'+row.savedraftby+'</span>';
                else
                    return '-';
            }
        },
        {
            "data": 'savedraftat',
            "render": function (data, type, row)  {
                if(row.savedraftat != null)
                    return '<span>'+row.savedraftat+', '+moment(row.savedraftat).format('DD/MM/YYYY hh:mm A')+'</span>';
                else
                    return '-';
            }
        },
        {
            "data": 'totaldayspending',
            "render": function (data, type, row)  {
                return '<span>'+row.totaldayspending+'</span>';
            }
        },
    ],
    ajax: {
        method: 'get',
        url: config.routes.point.dsu.data,
        dataSrc: "data",
        data: function (d) {
            d.dateRange = $('#filterdate').val();
        },
        dataType: "json",
    },
});

$(document).ready(function() {
    var currentDate = moment();

    $('#filterdate').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        },
    }).on('apply.daterangepicker', function(ev, picker) {
        table.ajax.reload(); 
    });
});