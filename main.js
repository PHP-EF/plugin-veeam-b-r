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

$("#VeeamPluginSessionTable").bootstrapTable();
