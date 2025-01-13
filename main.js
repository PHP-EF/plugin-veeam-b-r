function veeamButtonFormatter(value, row, index) {
    var actions = [
        `<a class="details" title="Details"><i class="fa fa-gear"></i></a>&nbsp;`
    ];
    return actions.join("");
}

function veeamResultFormatter(value, row, index) {
    var badgeClass = getStatusClass(value);
    var actions = [
        `<span class="badge `+badgeClass+`">`+value+`</span>`
    ];
    return actions.join("");
}

function veeamCanceledFormatter(value, row, index) {
    if (value == true) {
        return `<span class="badge bg-danger">Yes</span>`
    } else {
        return `<span class="badge bg-success">No</span>`
    }
}

function progressFormatter(value, row, index) {
    return `<div class="progress" role="progressbar" aria-valuenow="`+value+`" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar bg-info text-dark" style="width: `+value+`%">`+value+`%</div>
    </div>`;
}

window.veeamButtonEvents = {
    "click .details": function (e, value, row, index) {
        veeamSessionDetailsPopulate(row);
    }
}

function veeamSessionDetailsPopulate(row) {
    $("#resultStatus").text(row.result.result).addClass(getStatusClass(row.result.result));
    $("#resultMessage").text(row.result.message);
    $("#resultCanceled").text(row.result.isCanceled);
    buildTaskSessionTable(row.id);
    $("#sessionDetailsModal").modal("show");
}

function getStatusClass(status) {
    switch (status) {
        case "Success":
            return "bg-success";
            break;
        case "Warning":
            return "bg-warning";
            break;
        case "Failed":
            return "bg-danger";
            break;
        case "None":
            return "bg-primary";
    }
}

function buildTaskSessionTable(sessionId) {
    queryAPI("GET","/api/plugin/VeeamPlugin/sessions/"+sessionId+"/taskSessions").done(function(data) {
        if (data["result"] == "Success") {
            var tableData = data.data.data;
            $("#taskSessionTable").bootstrapTable("destroy");
            $("#taskSessionTable").bootstrapTable({
            data: tableData,
            sortable: true,
            pagination: true,
            search: true,
            showExport: true,
            exportTypes: ["json", "xml", "csv", "txt", "excel", "sql"],
            showColumns: true,
            showRefresh: true,
            filterControl: true,
            filterControlVisible: false,
            showFilterControlSwitch: true,
            buttons: "userButtons",
            buttonsOrder: "btnAddUser,btnBulkDelete,refresh,columns,export,filterControlSwitch",
            columns: [{
                field: "algorithm",
                title: "Algorithm",
                filterControl: "input",
                sortable: true
            },{
                field: "state",
                title: "State",
                filterControl: "input",
                sortable: true
            },{
                field: "name",
                title: "Name",
                filterControl: "input",
                sortable: true
            },{
                field: "result.result",
                title: "Result",
                formatter: "veeamResultFormatter",
                filterControl: "input",
                sortable: true
            },{
                field: "result.message",
                title: "Message",
                filterControl: "input",
                sortable: true
            },{
                field: "result.isCanceled",
                title: "Cancelled",
                filterControl: "input",
                formatter: "veeamCanceledFormatter",
                sortable: true
            },{
                field: "progress.bottleneck",
                title: "Bottleneck",
                filterControl: "input",
                sortable: true
            },{
                field: "progress.processingRate",
                title: "Rate",
                filterControl: "input",
                sortable: true
            },{
                field: "progress.processedSize",
                title: "Process Size",
                filterControl: "input",
                sortable: true
            },{
                field: "type",
                title: "Type",
                filterControl: "input",
                sortable: true
            },{
                field: "sessionType",
                title: "Session Type",
                filterControl: "input",
                sortable: true
            },{
                field: "creationTime",
                title: "Creation  Date/Time",
                filterControl: "input",
                sortable: false,
                visible: false,
                formatter: "datetimeFormatter"
            },{
                field: "endTime",
                title: "End Date/Time",
                filterControl: "input",
                sortable: false,
                visible: false,
                formatter: "datetimeFormatter"
            }]
            });
            // Enable refresh button
            $(`button[name="refresh"]`).click(function() {
                buildTaskSessionTable(sessionId);
            });
        } else {
            toast(data["status"],"",data["message"],"danger","30000");
        }
    }).fail(function( data, status ) {
        toast("API Error","","Unknown API Error","danger","30000");
    })
}

$("#VeeamPluginSessionTable").bootstrapTable();
