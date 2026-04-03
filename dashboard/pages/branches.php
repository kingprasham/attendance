<?php
session_start();
define('BASE_URL', rtrim(str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/')), '/') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Branches';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO branches (name, address, latitude, longitude, geofence_radius, late_threshold_hour, late_threshold_minute)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['address']),
            (float)$_POST['latitude'],
            (float)$_POST['longitude'],
            (int)($_POST['geofence_radius'] ?: 200),
            (int)($_POST['late_hour'] ?: 9),
            (int)($_POST['late_minute'] ?: 30),
        ]);
        $msg = 'Branch created successfully.';

    } elseif ($action === 'update') {
        $stmt = $db->prepare("UPDATE branches SET name=?, address=?, latitude=?, longitude=?,
                              geofence_radius=?, late_threshold_hour=?, late_threshold_minute=?
                              WHERE id=?");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['address']),
            (float)$_POST['latitude'],
            (float)$_POST['longitude'],
            (int)($_POST['geofence_radius'] ?: 200),
            (int)($_POST['late_hour'] ?: 9),
            (int)($_POST['late_minute'] ?: 30),
            (int)$_POST['branch_id'],
        ]);
        $msg = 'Branch updated successfully.';

    } elseif ($action === 'delete') {
        $db->prepare("UPDATE branches SET is_active = 0 WHERE id = ?")->execute([(int)$_POST['branch_id']]);
        $msg = 'Branch deleted.';
        $msgType = 'warning';
    }
}

$branches = $db->query("
    SELECT b.*, COUNT(e.id) AS employee_count
    FROM branches b
    LEFT JOIN employees e ON e.branch_id = b.id AND e.is_active = 1
    WHERE b.is_active = 1
    GROUP BY b.id
    ORDER BY b.name
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 text-muted"><?= count($branches) ?> active branch(es)</h6>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#branchModal"
            onclick="openBranchModal()">
        <i class="bi bi-plus-circle me-1"></i>New Branch
    </button>
</div>

<div class="row g-3">
<?php foreach ($branches as $b): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($b['name']) ?></h6>
                    <span class="badge bg-primary-subtle text-primary">
                        <?= $b['employee_count'] ?> employee(s)
                    </span>
                </div>
                <p class="text-muted small mb-2"><?= htmlspecialchars($b['address']) ?></p>
                <div class="small text-muted">
                    <i class="bi bi-geo me-1"></i><?= number_format($b['latitude'], 6) ?>, <?= number_format($b['longitude'], 6) ?>
                    &nbsp;·&nbsp;
                    <i class="bi bi-bullseye me-1"></i><?= $b['geofence_radius'] ?>m radius
                </div>
                <div class="small text-muted mt-1">
                    <i class="bi bi-clock me-1"></i>Late after
                    <?= sprintf('%02d:%02d', $b['late_threshold_hour'], $b['late_threshold_minute']) ?> IST
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm flex-fill"
                        onclick="openBranchModal(<?= htmlspecialchars(json_encode($b)) ?>)">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
                <form method="POST" class="flex-fill"
                      onsubmit="return confirm('Delete branch <?= htmlspecialchars($b['name']) ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($branches)): ?>
    <div class="col-12 text-center text-muted py-5">No branches yet. Create one to get started.</div>
<?php endif; ?>
</div>

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="branchForm">
            <input type="hidden" name="action" id="branchAction" value="create">
            <input type="hidden" name="branch_id" id="branchId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchModalTitle">New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Name *</label>
                            <input type="text" name="name" id="bName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Geofence Radius (meters)</label>
                            <input type="number" name="geofence_radius" id="bRadius" class="form-control" value="200" min="50" max="5000">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" name="address" id="bAddress" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Late After (Hour)</label>
                            <input type="number" name="late_hour" id="bLateHour" class="form-control" value="9" min="0" max="23">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Late After (Minute)</label>
                            <input type="number" name="late_minute" id="bLateMin" class="form-control" value="30" min="0" max="59">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Latitude *</label>
                            <input type="number" step="any" name="latitude" id="bLat" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Longitude *</label>
                            <input type="number" step="any" name="longitude" id="bLng" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <div id="map" style="height:300px;border-radius:8px;border:1px solid #dee2e6"></div>
                            <small class="text-muted">Click on the map to set the branch location.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Branch</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Google Maps -->
<script>
let map, marker;

function initMap() {
    const defaultCenter = { lat: 19.0760, lng: 72.8777 }; // Mumbai default
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 14,
        center: defaultCenter,
    });
    marker = new google.maps.Marker({ map, draggable: true, position: defaultCenter });

    map.addListener('click', (e) => setMarker(e.latLng.lat(), e.latLng.lng()));
    marker.addListener('dragend', () => {
        setMarker(marker.getPosition().lat(), marker.getPosition().lng());
    });
}

function setMarker(lat, lng) {
    marker.setPosition({ lat, lng });
    document.getElementById('bLat').value = lat.toFixed(7);
    document.getElementById('bLng').value = lng.toFixed(7);
}

function openBranchModal(branch = null) {
    document.getElementById('branchModalTitle').textContent = branch ? 'Edit Branch' : 'New Branch';
    document.getElementById('branchAction').value = branch ? 'update' : 'create';
    document.getElementById('branchId').value      = branch?.id ?? '';
    document.getElementById('bName').value         = branch?.name ?? '';
    document.getElementById('bAddress').value      = branch?.address ?? '';
    document.getElementById('bRadius').value       = branch?.geofence_radius ?? 200;
    document.getElementById('bLateHour').value     = branch?.late_threshold_hour ?? 9;
    document.getElementById('bLateMin').value      = branch?.late_threshold_minute ?? 30;

    const lat = parseFloat(branch?.latitude ?? 19.0760);
    const lng = parseFloat(branch?.longitude ?? 72.8777);
    document.getElementById('bLat').value = lat;
    document.getElementById('bLng').value = lng;

    // Update map when modal opens
    const modal = document.getElementById('branchModal');
    modal.addEventListener('shown.bs.modal', () => {
        google.maps.event.trigger(map, 'resize');
        map.setCenter({ lat, lng });
        marker.setPosition({ lat, lng });
    }, { once: true });
}
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBHmCt1bQGIJXTJHkkggsBzIqanAbdkfJk&callback=initMap">
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
