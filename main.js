function veeamButtonFormatter(value, row, index) {
      var actions = [
        `<a class="details" title="Details"><i class="fa fa-gear"></i></a>&nbsp;`
      ];
      return actions.join("");
  }

  window.veeamButtonEvents = {
    "click .details": function (e, value, row, index) {
        veeamSessionDetailsPopulate(row);
    }
  }

function veeamSessionDetailsPopulate (row) {
    switch (row.result.result) {
        case "Success":
            $("#resultStatus").addClass("bg-success");
            break;
        case "Warning":
            $("#resultStatus").addClass("bg-warning");
            break;
        case "Failed":
            $("#resultStatus").addClass("bg-danger");
            break;
    }
    $("#resultStatus").text(row.result.result);
    $("#resultMessage").text(row.result.message);
    $("#resultCanceled").text(row.result.isCanceled);
    $("#sessionDetailsModal").modal("show");
}

$("#VeeamPluginSessionTable").bootstrapTable();
