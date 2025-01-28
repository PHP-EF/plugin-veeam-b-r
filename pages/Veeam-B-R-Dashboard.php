<?php
$VeeamPlugin = new VeeamPlugin();
$pluginConfig = $VeeamPlugin->config->get('Plugins', 'VeeamPlugin');
if ($VeeamPlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? "ACL-READ") == false) {
    $VeeamPlugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return false;
}
return '
<style>
    .small-box {
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }

    .small-box > .inner {
        padding: 20px;
    }

    .small-box h3 {
        font-size: 2.2rem;
        font-weight: bold;
        margin: 0 0 10px 0;
        white-space: nowrap;
        padding: 0;
        color: #fff;
    }

    .small-box p {
        font-size: 1rem;
        color: #fff;
        margin-bottom: 0;
    }

    .small-box .icon {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 2.5rem;
        color: rgba(255,255,255,0.3);
    }

    .bg-info {
        background-color: #17a2b8 !important;
    }

    .bg-success {
        background-color: #28a745 !important;
    }

    .bg-warning {
        background-color: #ffc107 !important;
    }

    .bg-warning p {
        color: #000 !important;
    }

    /* Toggle switch styles */
    .switch-container {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        margin-right: 8px;
    }
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider {
        background-color: #2196F3;
    }
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    .system-job.hidden {
        display: none;
    }
</style>

<!-- License Summary Section -->
<div class="container mb-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">License Usage Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalVMsProtected">-</h3>
                                    <p>Total VMs Protected</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-server"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="totalInstancesUsed">-</h3>
                                    <p>Total Instances Used</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-key"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="vcenterHost">-</h3>
                                    <p>vCenter Host</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-network-wired"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Sessions Table -->
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Veeam Backup Sessions</h3>
                        <div class="switch-container">
                            <label class="switch">
                                <input type="checkbox" id="systemJobToggle" checked>
                                <span class="slider"></span>
                            </label>
                            <span>Show System Jobs</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table data-url="/api/plugin/VeeamPlugin/sessions"
                        data-data-field="data"
                        data-toggle="table"
                        data-search="true"
                        data-filter-control="true"
                        data-show-filter-control-switch="true"
                        data-filter-control-visible="false"
                        data-show-refresh="true"
                        data-pagination="true"
                        data-toolbar="#toolbar"
                        data-sort-name="Name"
                        data-sort-order="asc"
                        data-show-columns="true"
                        data-page-size="25"
                        // data-buttons="rbacGroupsButtons"
                        // data-buttons-order="btnAddGroup,refresh"
                        class="table table-striped" id="VeeamPluginSessionTable">
                        <thead>
                        <tr>
                            <th data-field="state" data-checkbox="true"></th>
                            <th data-field="name" data-sortable="true" data-filter-control="select">Job Name</th>
                            <th data-field="creationTime" data-sortable="true" data-formatter="datetimeFormatter" data-filter-control="input">Start Time</th>
                            <th data-field="endTime" data-sortable="true" data-formatter="datetimeFormatter" data-filter-control="input">End Time</th>
                            <th data-field="result.message" data-sortable="true" data-visible="false" data-filter-control="input">Message</th>
                            <th data-formatter="progressFormatter" data-field="progressPercent" data-sortable="true" data-filter-control="input">Progress</th>
                            <th data-formatter="veeamResultFormatter" data-field="result.result" data-sortable="true" data-filter-control="select">Result</th>
                            <th data-formatter="veeamCanceledFormatter" data-field="result.isCanceled" data-sortable="true" data-filter-control="select">Cancelled</th>
                            <th data-formatter="veeamButtonFormatter" data-events="veeamButtonEvents">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="veeamSessions"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for showing session details -->
<div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-labelledby="sessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionDetailsModalLabel">Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8"><span id="resultStatus" class="badge"></span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Message:</strong></div>
                    <div class="col-8" id="resultMessage"></div>
                </div>
                <div class="row">
                    <div class="col-4"><strong>Canceled:</strong></div>
                    <div class="col-8" id="resultCanceled"></div>
                </div>
                <table class="table table-striped" id="taskSessionTable"></table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const systemJobs = ["Malware Detection","Backup Configuration Job", "Configuration Database Resynchronize", "Security & Compliance Analyzer"];
    
    function toggleSystemJobs(show) {
        const $table = $("#VeeamPluginSessionTable");
        
        if (show) {
            $table.bootstrapTable("clearFilterControl");
            $table.bootstrapTable("refresh");
        } else {
            const data = $table.bootstrapTable("getData");
            const filteredData = data.filter(function(row) {
                if (!row.name) return true;
                return !systemJobs.includes(row.name);
            });
            $table.bootstrapTable("load", filteredData);
        }
    }

    $("#systemJobToggle").on("change", function() {
        toggleSystemJobs(this.checked);
    });

    toggleSystemJobs(true);

    $("#VeeamPluginSessionTable").on("load-success.bs.table", function(e, data) {
        const columns = $(this).bootstrapTable("getOptions").columns[0];
    });

    // Load license data
    function loadLicenseData() {
        // console.log(\'Loading license data...\');
        queryAPI(\'GET\', \'/api/plugin/VeeamPlugin/licenseinstances\')
            .then(function(response) {
                if (response.result === \'Success\' && response.data && response.data.data) {
                    const data = response.data.data;
                    // console.log(\'License data:\', data);
                    
                    // Calculate totals
                    const totalVMs = data.length;
                    const totalInstances = data.reduce((sum, item) => sum + (parseInt(item.usedInstancesNumber) || 0), 0);
                    const vcenterHost = data[0]?.hostName || \'N/A\';
                    
                    // console.log(\'Calculated values:\', {
                    //     totalVMs,
                    //     totalInstances,
                    //     vcenterHost
                    // });
                    
                    // Update the dashboard
                    $(\'#totalVMsProtected\').text(totalVMs);
                    $(\'#totalInstancesUsed\').text(totalInstances);
                    $(\'#vcenterHost\').text(vcenterHost);
                } else {
                    console.error(\'Invalid license data response:\', response);
                    $(\'#totalVMsProtected\').text(\'Error\');
                    $(\'#totalInstancesUsed\').text(\'Error\');
                    $(\'#vcenterHost\').text(\'Error\');
                }
            })
            .catch(function(error) {
                console.error(\'Error loading license data:\', error);
                $(\'#totalVMsProtected\').text(\'Error\');
                $(\'#totalInstancesUsed\').text(\'Error\');
                $(\'#vcenterHost\').text(\'Error\');
            });
    }
    
    // Load initial data
    loadLicenseData();
    
    // Refresh data every 5 minutes
    setInterval(loadLicenseData, 5 * 60 * 1000);
});
</script>
';
?>
