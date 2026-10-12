<?php
    // include files
    require_once('../config/connection.php');
    require_once('manage_authorization.php');

    $remote_addr = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Checking authirization details
    $is_break = 0;
    $is_error = 0;
    $is_data = 0;

    if (count($authorized_devices) == count($authorized_pages_per_device)) {
        foreach ($authorized_devices as $key_1 => $value_1) {
            foreach ($authorized_devices[$key_1] as $key_2 => $value_2) {
                if (count($authorized_devices[$key_1]) == 2) {
                    if ($authorized_devices[$key_1][0] != '127.0.0.1' && $authorized_devices[$key_1][0] != '::1' && !str_starts_with($authorized_devices[$key_1][0], '192.168.')) {
                        $is_break = 1;
                        $is_error = 1;
                        break;
                    }

                    if ($value_2 == $remote_addr) {
                        $is_data = 1;
                    }

                    if ($value_2 == $user_agent && $is_data == 1) {
                        /* Valid authorized staff member */
                        /* checking authorization pages */
                        foreach ($authorized_pages_per_device as $key_3 => $value_3) {
                            foreach ($authorized_pages_per_device[$key_3] as $key_4 => $value_4) {
                                if (count($authorized_pages_per_device[$key_3]) > 7) {
                                    $is_break = 1;
                                    $is_error = 1;
                                    break;
                                }

                                if ($value_4 < 1 || $value_4 > 7) {
                                    $is_break = 1;
                                    $is_error = 1;
                                    break;
                                }

                                if ($key_3 == $key_1) {
                                    /* Setup the 'Manage Menu Items' page number */
                                    if ($value_4 == '02') {
                                        /* Authorized successfully */
                                        $is_break = 1;
                                        break;
                                    }
                                } else if ($key_3 > $key_1) {
                                    $is_break = 1;
                                    $is_error = 1;
                                    break;
                                }
                            }

                            if ($is_break == 1) {
                                break;
                            }
                        }

                        if ($is_break == 1) {
                            break;
                        }
                    }

                    if ($key_2 == 1) {
                        $is_data = 0;
                    }
                } else {
                    $is_break = 1;
                    $is_error = 1;
                    break;
                }
            }

            if ($is_break == 1) {
                break;
            }
        }

        if ($is_error == 1 || $is_data == 0) {
            include("../errors/403.php");
            exit();
        }
    } else {
        include("../errors/403.php");
        exit();
    }

    ///////////////////////// isset REQUEST method data //////////////////////
    try {
        if(isset($_POST['table_number']) && trim($_POST['table_number']) != '') {
            $table_number = trim($_POST['table_number']);
            $hash_code = trim($_POST['hash_code']);
            $location = trim($_POST['location']);
            $table_section = trim($_POST['table_section']);
            $is_active = trim($_POST['is_active']);

            if (strlen($table_number) > 10 || !preg_match('/^[A-Za-z0-9]+$/', $table_number)) {
                die("Invalid table number.");
            }
            if (strlen($hash_code) > 32 || !preg_match('/^[A-Za-z0-9]+$/', $hash_code)) {
                die("Invalid Hash code.");
            }
            if ($location !== '' && (strlen($location) > 20 || !preg_match('/^[A-Za-z ]+$/', $location))) {
                die("Invalid location.");
            }
            if (strlen($table_section) > 7 || !preg_match('/[a-z]+/', $table_section)) {
                die("Invalid table section.");
            }
            if (strlen($is_active) > 1 || !preg_match('/^[0-1]$/', $is_active)) {
                die("Invalid is active.");
            }

            if($location !== '') {
                $sql = "INSERT INTO `restaurant_tables`(`table_number`, `location`, `table_section`, `is_active`) VALUES (:table_number, :location, :table_section, :is_active)";
            } else {
                $sql = "INSERT INTO `restaurant_tables`(`table_number`, `table_section`, `is_active`) VALUES (:table_number, :table_section, :is_active)";
            }
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':table_number', $hash_code);

            if($location !== '') {
                $stmt -> bindValue(':location', $location);
            }

            $stmt -> bindValue(':table_section', $table_section);
            $stmt -> bindValue(':is_active', $is_active);
            $stmt -> execute();
    
            // inserted data successfully
            header('Location: /addTable');
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////////////// Update details ///////////////////////
    
    try {
        if(isset($_POST['update_records'])) {
            $fields_config = [
                'table_number' => ['regex' => '/^[A-Za-z0-9]+$/', 'max' => 10, 'nullable' => true],
                'hash_code' => ['regex' => '/^[A-Za-z0-9]+$/', 'max' => 32, 'nullable' => false],
                'location' => ['regex' => '/^[A-Za-z ]+$/', 'max' => 20, 'nullable' => true],
                'table_section' => ['regex' => '/[a-z]+/', 'max' => 7, 'nullable' => false],
                'is_active' => ['regex' => '/^[0-1]$/', 'max' => 1, 'nullable' => false]
            ];

            $table_number = '';

            foreach($_POST as $key => $value) {
                if ($key === 'update_records') continue;

                $table_id = substr(strstr($key, '-'), 1);
                $column = substr($key, 0, -(strlen($table_id) + 1));

                if (!isset($fields_config[$column])) continue;
            
                $config = $fields_config[$column];
                $value = trim($value); // remove spaces

                // validate input
                if ($value === '' && $config['nullable']) {
                    $cell_value = NULL;
                } else {
                    if (strlen($value) > $config['max'] || !preg_match($config['regex'], $value)) die("Invalid $column.");
                }

                // convert columns
                if($column == 'table_number' && $value !== '') {
                    $table_number = $value;
                } else {
                    if($column == 'hash_code') {
                        if($table_number === '') {
                            $sql = "SELECT * FROM `restaurant_tables` WHERE `id` = :table_id AND `table_number` = :hash_code LIMIT 1";
                            $stmt = $pdo -> prepare($sql);
                            $stmt -> bindValue(':table_id', $table_id);
                            $stmt -> bindValue(':hash_code', $value);
                            $stmt -> execute();
                            $row = $stmt -> fetch(PDO::FETCH_ASSOC);
                            if($row == FALSE) die("Invalid hash code.");
                        } else {
                            if(md5($table_number) != $value) {
                                die("Invalid hash code.");
                            }
                        }
                        $column = 'table_number';
                        $table_number = '';
                    }

                    // build and run query
                    if($column != 'table_number' || ($column == 'table_number' && $value !== '')) {
                        $sql = "UPDATE `restaurant_tables` SET `$column` = :cell_value WHERE `id` = :item_id";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindValue(':cell_value', $value);
                        $stmt->bindValue(':item_id', $table_id);
                        $stmt->execute();
                    }
                }
            }

            // updated successfully
            header('Location: /addTable');
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////// check is set filter button /////////////
    try {
        if(isset($_REQUEST['table_section'])) {
            $sql = "SELECT * FROM `restaurant_tables` WHERE `table_section` = :table_section ORDER BY `id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':table_section', trim($_REQUEST['table_section']));
        } else {
            $sql = "SELECT * FROM `restaurant_tables` ORDER BY `id` ASC";
            $stmt = $pdo -> prepare($sql);
        }
        $stmt -> execute();
        $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }
?>

<!-- HTML --->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lavor Hub Restaurant - Add Restaurant Tables</title>
    <link rel="icon" href="/img/logo.jpg">
    <link rel="stylesheet" href="/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/add_pages.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>🍽️ Lavor Hub Restaurant 🍴</h1>
        <address>📍 160A /1.Dehiwala Road, Maharagama.</address>
    </header>
    <main>
        <div class="main-header">
            <h3>💻 Manage Restaurant Tables 🪑</h3>
            <p>📠 Computer IP Address - <?= htmlspecialchars($remote_addr) ?></p>
        </div>
        <div class="main-content">
            <form action="/addTable" method="post">
                <fieldset>
                    <legend>NEW +</legend>
                    <label>Table Number*:<input type="text" name="table_number" id="table_number" maxlength="10" placeholder="Table Number (e.g. T1, A12, etc.)" required></label>
                    <label>Hash code (Auto generated):<input type="text" name="hash_code" id="hash_code" maxlength="32" placeholder="Auto Generated Hash Code" readonly></label>
                    <label>Location (Optional):<input type="text" name="location" id="" maxlength="20" placeholder="Location (e.g. Patio, First Floor, etc.)"></label>
                    <label>Table Section:<select name="table_section" id=""><option value="kitchen">Kitchen</option><option value="bar">Bar</option></select></label>
                    <label>Is Active:<select name="is_active" id=""><option value="1">1</option><option value="0">0</option></select></label>
                    <div class="form-actions">
                        <button type="submit" onclick="return confirm('Are you sure you want to save new table details ?')">SAVE NEW RESTAURANT TABLE</button>
                        <button type="reset">RESET ALL</button>
                    </div>
                </fieldset>
            </form>
            <form action="/addTable" method="post">
                <fieldset>
                    <legend>UPDATE ▲</legend>
                    <a href="/addTable/showKitchen"><button type="button">Show Kitchen Tables</button></a>
                    <a href="/addTable/showBar"><button type="button">Show Bar Tables</button></a>
                    <button type="submit" name="update_records" onclick="return confirm('Are you sure you want to update last changes ?')">UPDATE LAST CHANGES (IF ANY) ☼</button>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Table Number</th>
                                    <th>Hash Code (Auto Generated)</th>
                                    <th>Location</th>
                                    <th>Table Section</th>
                                    <th>Is Active</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($row as $key => $value): ?>
                                    <tr>
                                        <td><input type="number" name="" id="" value="<?= htmlspecialchars($value['id']) ?>" readonly></td>
                                        <td><input type="text" name="table_number-<?=$value['id']?>" id="" maxlength="10" placeholder="Re-Table Number (e.g. T1, A12, etc.)"></td>
                                        <td><input type="text" name="hash_code-<?=$value['id']?>" id="" value="<?= htmlspecialchars($value['table_number']) ?>" maxlength="32" placeholder="Auto Generated Hash Code" readonly></td>
                                        <td><input type="text" name="location-<?=$value['id']?>" id="" value="<?= htmlspecialchars($value['location'] ?? '') ?>" maxlength="20" placeholder="Location (e.g. Patio, First Floor, etc.)"></td>
                                        <td><select name="table_section-<?=$value['id']?>" id=""><option value="kitchen" <?= ($value['table_section'] == "kitchen") ? "selected" : '' ?>>Kitchen</option><option value="bar" <?= ($value['table_section'] == "bar") ? "selected" : '' ?>>Bar</option></select></td>
                                        <td><select name="is_active-<?=$value['id']?>" id=""><option value="1" <?= ($value['is_active'] == 1) ? "selected" : '' ?>>1</option><option value="0" <?= ($value['is_active'] == 0) ? "selected" : '' ?>>0</option></select></td>
                                        <td><input type="datetime-local" name="" id="" value="<?= htmlspecialchars($value['created_at']) ?>"></td>
                                        <td><input type="datetime-local" name="" id="" value="<?= htmlspecialchars($value['updated_at']) ?>"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </fieldset>
            </form>
        </div>
    </main>
    <footer>
        <h4>&copy; 2026 Lavor Hub Restaurant. All rights reserved.</h4>
    </footer>

    <!-- Js -->
    <script src="/js/md5.min.js"></script>
    <script src="/js/autofill_hash_code.js"></script>
</body>
</html>
