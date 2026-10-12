<?php
    // include files
    require_once('../config/connection.php');
    require_once('manage_authorization.php');
    set_time_limit(310);

    $message_box = array();
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
                                    if ($value_4 == '05') {
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

    ////////////// running loop while one or more records found in lobby ///////////////////
    try {
        $start_time = time();
        $timeout = 300; // seconds

        while(true) {
            if((time() - $start_time) > $timeout) {
                $message_box[] = "The request timed out and the system couldn't find one or more users for 5 minutes.";
                $message_box[] = "<a href='/lobby'>Retry 🔄️</a>";
                break;
            }

            // searching a one record in db table
            $sql = "SELECT
                        `remote_address`,
                        `user_agent`,
                        `temporary_name`,
                        `users`.`is_active` AS `user_is_active`,
                        `users`.`created_at` AS `user_created_at`,
                        `users`.`updated_at` AS `user_updated_at`,
                        `table_number`,
                        `location`,
                        `table_section`,
                        `restaurant_tables`.`is_active` AS `table_is_active`,
                        `restaurant_tables`.`created_at` AS `table_created_at`,
                        `restaurant_tables`.`updated_at` AS `table_updated_at`,
                        `table_users`.`id` AS `table_user_id`,
                        `user_id`,
                        `table_id`,
                        `table_users`.`created_at` AS `table_user_created_at`,
                        `table_users`.`updated_at` AS `table_user_updated_at`
                    FROM `table_users`, `restaurant_tables`, `users`
                    WHERE
                        `table_users`.`user_id` = `users`.`id` AND
                        `table_users`.`table_id` = `restaurant_tables`.`id` AND
                        `table_users`.`is_active` = :is_active
                    ORDER BY `table_users`.`id` ASC
                    LIMIT 1";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 2);
            $stmt -> execute();
            $row = $stmt -> fetch(PDO::FETCH_ASSOC);

            // checks query result
            if($row) {
                break; // found a one record
            } else {
                sleep(1); // system wait 1 sec & continue
                continue;
            }
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////////// UPDATE TEMPORARY NAME ////////////////
    try {
        if(isset($_POST['temporary_name']) && strlen(trim($_POST['temporary_name'])) > 0) {
            $sql = "UPDATE `users` SET `temporary_name` = :temporary_name WHERE `id` = :user_id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':temporary_name', trim($_POST['temporary_name']));
            $stmt -> bindValue(':user_id', $row['user_id']);
            $stmt -> execute();

            // redirect this page
            header('Location: /lobby');
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////////// UPDATE TABLE_USERS TABLE ////////////////
    try {
        if(isset($_POST['command']) && $_POST['command'] == "yes") {
            $sql = "UPDATE `table_users` SET `is_active` = :is_active WHERE `id` = :table_user_id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':table_user_id', $row['table_user_id']);
            $stmt -> execute();

            // redirect this page
            header('Location: /lobby');
            exit();
        } else if(isset($_POST['command']) && $_POST['command'] == "no") {
            $sql = "UPDATE `table_users` SET `is_active` = :is_active WHERE `id` = :table_user_id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 0);
            $stmt -> bindValue(':table_user_id', $row['table_user_id']);
            $stmt -> execute();

            // redirect this page
            header('Location: /lobby');
            exit();
        }
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
    <link rel="stylesheet" href="/css/lobby.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>🍽️ Lavor Hub Restaurant 🍴</h1>
        <address>📍 160A /1.Dehiwala Road, Maharagama.</address>
    </header>
    <main>
        <div class="main-header">
            <h3>🛡️ Lobby Users ⏱️</h3>
            <p>🔰 Computer IP Address - <?= htmlspecialchars($remote_addr) ?> 🔰</p>
        </div>
        <div class="main-content">
            <?php if(empty($message_box)): ?><hr/>
                <h2>💠 Lobby Room Latest Record Details 💠</h2><hr/>
                <?php if($row['temporary_name'] !== NULL): ?>
                    <h3>🪪 The friend, <?= htmlspecialchars($row['temporary_name']) ?> is waiting in lobby... 🙋‍♂️</h3>
                <?php endif; ?>

                <!-- customer table info -->
                <table>
                    <caption>👤 Customer Info ℹ️</caption>
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Remote Addr</th>
                            <th>User Agent</th>
                            <th>Temporary Name</th>
                            <th>Is Active</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['remote_address']) ?></td>
                            <td><?= htmlspecialchars($row['user_agent']) ?></td>
                            <td><form action="/lobby" method="post"><input type="text" name="temporary_name" id="" value="<?= htmlspecialchars($row['temporary_name'] ?? '') ?>" maxlength="100" placeholder="Enter user temporary name.." required></form></td>
                            <td><?= htmlspecialchars($row['user_is_active']) ?></td>
                            <td><?= htmlspecialchars($row['user_created_at']) ?></td>
                            <td><?= htmlspecialchars($row['user_updated_at']) ?></td>
                        </tr>
                    </tbody>
                </table>
                <p>Note: If the customer is a friend of the staff, the staff can enter any name as a temporary name to use on behalf of the customer.</p>

                <!-- restaurant table info -->
                <table>
                    <caption>🧱 Restaurant Table Info ℹ️</caption>
                    <thead>
                        <tr>
                            <th>Table ID</th>
                            <th>Table Number</th>
                            <th>Location</th>
                            <th>Table Section</th>
                            <th>Is Active</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= htmlspecialchars($row['table_id']) ?></td>
                            <td><?= htmlspecialchars($row['table_number']) ?></td>
                            <td><?= htmlspecialchars($row['location'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['table_section']) ?></td>
                            <td><?= htmlspecialchars($row['table_is_active']) ?></td>
                            <td><?= htmlspecialchars($row['table_created_at']) ?></td>
                            <td><?= htmlspecialchars($row['table_updated_at']) ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- restaurant table usage info -->
                <?php
                    try {
                        $outer_sql = "SELECT * FROM `table_users` WHERE `table_id` = :table_id ORDER BY `id` DESC";
                        $outer_stmt = $pdo -> prepare($outer_sql);
                        $outer_stmt -> bindValue(':table_id', $row['table_id']);
                        $outer_stmt -> execute();
                        $outer_row = $outer_stmt -> fetchAll(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {
                        die("Error: " . $e -> getMessage());
                    }
                ?>
                <table>
                    <caption>🧑‍💻 Restaurant Table Usage Info ℹ️</caption>
                    <thead>
                        <tr>
                            <th>Reserved ID</th>
                            <th>Customer ID</th>
                            <th>Is Active</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($outer_row): ?>
                            <?php foreach($outer_row as $key => $value): ?>
                                <tr>
                                    <td><?= htmlspecialchars($value['id']) ?></td>
                                    <td><?= htmlspecialchars($value['user_id']) ?></td>
                                    <td><?= htmlspecialchars($value['is_active']) ?></td>
                                    <td><?= htmlspecialchars($value['created_at']) ?></td>
                                    <td><?= htmlspecialchars($value['updated_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- popup window -->
                <form action="/lobby" method="post" class="popup-window">
                    <fieldset>
                        <legend>Command Prompt</legend>
                        <p>Are you give permission for this customer to access menu page to ?</p>
                        <button type="submit" name="command" value="yes">Yes</button>
                        <button type="submit" name="command" value="no">No</button>
                        <button type="button" onclick="">Cancel</button>
                    </fieldset>
                </form>
            <?php else: ?>
                <?php foreach($message_box as $key => $value): ?>
                    <p><?= $value ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <h4>&copy; 2026 Lavor Hub Restaurant. All rights reserved.</h4>
    </footer>
</body>
</html>
