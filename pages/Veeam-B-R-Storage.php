<?php
$VeeamPlugin = new VeeamPlugin();
$pluginConfig = $VeeamPlugin->config->get('Plugins', 'VeeamPlugin');
if ($VeeamPlugin->auth->checkAccess($pluginConfig['ACL-READ'] ?? "ACL-READ") == false) {
    $VeeamPlugin->api->setAPIResponse('Error', 'Unauthorized', 401);
    return false;
}
return '
<style>
    .storage-card {
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        background-color: #fff;
        padding: 20px;
    }
    
    .storage-card h4 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 1.2rem;
    }
    
    .storage-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2196F3;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #666;
    }
    
    .progress {
        height: 10px;
        margin-bottom: 10px;
    }
    
    .storage-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .status-online {
        background-color: #28a745;
        color: white;
    }
    
    .status-offline {
        background-color: #dc3545;
        color: white;
    }
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Backup Storage Overview</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalCapacity">-</h3>
                                    <p>Total Capacity</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-database"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="totalFree">-</h3>
                                    <p>Free Space</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="totalUsed">-</h3>
                                    <p>Used Space</p>
                                </div>
                                <div class="icon">
                                    <i class="fa fa-hdd-o"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Path</th>
                                    <th>Total Capacity</th>
                                    <th>Free Space</th>
                                    <th>Used Space</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="storageTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function formatSize(gb) {
    if (gb >= 1024) {
        return (gb / 1024).toFixed(1) + " TB";
    }
    return gb.toFixed(1) + " GB";
}

function loadStorageData() {
    console.log("Loading storage data...");
    queryAPI("GET", "/api/plugin/VeeamPlugin/backupinfrastructurestates")
        .then(function(response) {
            if (response.result === "Success" && response.data && response.data.data) {
                const data = response.data.data;
                console.log("Storage data:", data);
                
                // Calculate totals
                const totalCapacity = data.reduce((sum, item) => sum + (parseFloat(item.capacityGB) || 0), 0);
                const totalFree = data.reduce((sum, item) => sum + (parseFloat(item.freeGB) || 0), 0);
                const totalUsed = data.reduce((sum, item) => sum + (parseFloat(item.usedSpaceGB) || 0), 0);
                
                console.log("Calculated values:", {
                    totalCapacity,
                    totalFree,
                    totalUsed
                });
                
                // Update summary boxes
                $("#totalCapacity").text(formatSize(totalCapacity));
                $("#totalFree").text(formatSize(totalFree));
                $("#totalUsed").text(formatSize(totalUsed));
                
                // Update table
                const tableBody = $("#storageTableBody");
                tableBody.empty();
                
                data.forEach(function(storage) {
                    const row = `
                        <tr>
                            <td>${storage.name}</td>
                            <td>${storage.path}</td>
                            <td>${formatSize(storage.capacityGB)}</td>
                            <td>${formatSize(storage.freeGB)}</td>
                            <td>${formatSize(storage.usedSpaceGB)}</td>
                            <td><span class="badge ${storage.isOnline ? "bg-success" : "bg-danger"}">${storage.isOnline ? "Online" : "Offline"}</span></td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            } else {
                console.error("Invalid storage data response:", response);
                $("#totalCapacity").text("Error");
                $("#totalFree").text("Error");
                $("#totalUsed").text("Error");
                $("#storageTableBody").html("<tr><td colspan=\'6\' class=\'text-center\'>Error loading data</td></tr>");
            }
        })
        .catch(function(error) {
            console.error("Error loading storage data:", error);
            $("#totalCapacity").text("Error");
            $("#totalFree").text("Error");
            $("#totalUsed").text("Error");
            $("#storageTableBody").html("<tr><td colspan=\'6\' class=\'text-center\'>Error loading data</td></tr>");
        });
}

// Load initial data
loadStorageData();

// Refresh data every 5 minutes
setInterval(loadStorageData, 5 * 60 * 1000);
</script>
';