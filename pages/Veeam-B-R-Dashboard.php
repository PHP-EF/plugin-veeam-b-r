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

    .bg-warning h3,
    .bg-warning p {
        color: #000 !important;
    }
    
    /* Toggle switch styles */
    .switch-container {
        display: flex;
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
    
    .system-job {
        display: table-row;
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
                                    <h3 id="hostName" style="font-size: 1.2rem; word-break: break-all;">-</h3>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Veeam Backup Sessions</h3>
                    <div class="d-flex align-items-center">
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
                    <table id="sessionsTable" 
                           data-url="/api/plugin/VeeamPlugin/sessions"
                           data-toggle="table"
                           data-pagination="true"
                           data-search="true"
                           data-show-refresh="true"
                           data-show-toggle="true"
                           data-show-columns="true"
                           data-show-export="true"
                           data-minimum-count-columns="2"
                           data-show-pagination-switch="true"
                           data-page-list="[10, 25, 50, 100, ALL]"
                           data-show-footer="false">
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
document.addEventListener("DOMContentLoaded", function() {
    // Define system job types
    const systemJobs = ["Malware Detection", "Configuration Database Resynchronize", "Security & Compliance Analyzer"];
    
    // Function to mark system jobs
    function markSystemJobs() {
        const table = document.getElementById("sessionsTable");
        if (!table) return;
        
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach((row) => {
            const jobNameCell = row.querySelector("td:nth-child(2)"); // Adjust based on Job Name column position
            if (jobNameCell && systemJobs.includes(jobNameCell.textContent.trim())) {
                row.classList.add("system-job");
            }
        });
    }
    
    // Function to handle table load event
    function onTableLoad() {
        markSystemJobs();
        // Apply current toggle state
        const toggle = document.getElementById("systemJobToggle");
        if (toggle && !toggle.checked) {
            const systemJobRows = document.querySelectorAll(".system-job");
            systemJobRows.forEach((row) => row.classList.add("hidden"));
        }
    }
    
    // Toggle system jobs visibility
    const toggle = document.getElementById("systemJobToggle");
    if (toggle) {
        toggle.addEventListener("change", function() {
            const systemJobRows = document.querySelectorAll(".system-job");
            systemJobRows.forEach((row) => {
                if (this.checked) {
                    row.classList.remove("hidden");
                } else {
                    row.classList.add("hidden");
                }
            });
        });
    }
    
    // Initialize when table is loaded
    const table = document.getElementById("sessionsTable");
    if (table) {
        table.addEventListener("load-success.bs.table", onTableLoad);
        // Also run initial marking in case table is already loaded
        onTableLoad();
    }
});
</script>
';
?>
