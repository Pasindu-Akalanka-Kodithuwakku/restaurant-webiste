<?php
    // include files
    require_once('../config/connection.php');
    require_once('manage_authorization.php');

    $remote_addr = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $message_box = array();

    // update user updated_at as default table user (useful for QR scan)
    try {
        $sql = "UPDATE `users` SET `updated_at` = CURRENT_TIMESTAMP WHERE `remote_address` = :remote_address AND `user_agent` = :user_agent";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':remote_address', $remote_addr);
        $stmt -> bindValue(':user_agent', $user_agent);
        $stmt -> execute();
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

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
                                    if ($value_4 == '06') {
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

    ////////////// find records form tables ///////////////
    try {
        if(isset($_POST['table_id']) && isset($_POST['user_id']) && trim($_POST['table_id']) != '' && trim($_POST['user_id']) != '') {
            $sql = "SELECT
                        `name`,
                        SUM(`quantity`) AS `quantity`,
                        `price`,
                        `table_id`,
                        `user_id`,
                        `temporary_name`
                    FROM `menu_items`, `users`, `item_orders`
                    WHERE
                        `table_id` = (SELECT `table_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`user_id` = :user_id AND `table_users`.`table_id` = :table_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `user_id` = (SELECT `user_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`user_id` = :user_id AND `table_users`.`table_id` = :table_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `menu_items`.`id` = `item_orders`.`item_id` AND
                        `users`.`id` = `item_orders`.`user_id` AND
                        `delivered` = :delivered AND
                        `item_orders`.`is_active` = :is_active
                    GROUP BY `item_orders`.`item_id`
                    ORDER BY `item_orders`.`id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':user_id', trim($_POST['user_id']));
            $stmt -> bindValue(':table_id', trim($_POST['table_id']));
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> execute();
            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        } else if (isset($_POST['table_id']) && trim($_POST['table_id']) != '') {
            $sql = "SELECT
                        `name`,
                        SUM(`quantity`) AS `quantity`,
                        `price`,
                        `table_id`,
                        `user_id`,
                        `temporary_name`
                    FROM `menu_items`, `users`, `item_orders`
                    WHERE
                        `table_id` = (SELECT `table_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`table_id` = :table_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `user_id` = (SELECT `user_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`table_id` = :table_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `menu_items`.`id` = `item_orders`.`item_id` AND
                        `users`.`id` = `item_orders`.`user_id` AND
                        `delivered` = :delivered AND
                        `item_orders`.`is_active` = :is_active
                    GROUP BY `item_orders`.`item_id`
                    ORDER BY `item_orders`.`id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':table_id', trim($_POST['table_id']));
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> execute();
            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        } else if (isset($_POST['user_id']) && trim($_POST['user_id']) != '') {
            $sql = "SELECT
                        `name`,
                        SUM(`quantity`) AS `quantity`,
                        `price`,
                        `table_id`,
                        `user_id`,
                        `temporary_name`
                    FROM `menu_items`, `users`, `item_orders`
                    WHERE
                        `table_id` = (SELECT `table_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`user_id` = :user_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `user_id` = (SELECT `user_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `table_users`.`user_id` = :user_id AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `menu_items`.`id` = `item_orders`.`item_id` AND
                        `users`.`id` = `item_orders`.`user_id` AND
                        `delivered` = :delivered AND
                        `item_orders`.`is_active` = :is_active
                    GROUP BY `item_orders`.`item_id`
                    ORDER BY `item_orders`.`id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':user_id', trim($_POST['user_id']));
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> execute();
            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        } else if (isset($_POST['temporary_name']) && trim($_POST['temporary_name']) != '') {
            $sql = "SELECT
                        `name`,
                        SUM(`quantity`) AS `quantity`,
                        `price`,
                        `table_id`,
                        `user_id`,
                        `temporary_name`
                    FROM `menu_items`, `users`, `item_orders`
                    WHERE
                        `table_id` = (SELECT `table_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `users`.`temporary_name` = :temporary_name AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `user_id` = (SELECT `user_id` FROM `users`, `restaurant_tables`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `users`.`temporary_name` = :temporary_name AND `users`.`is_active` = :is_active AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `menu_items`.`id` = `item_orders`.`item_id` AND
                        `users`.`id` = `item_orders`.`user_id` AND
                        `delivered` = :delivered AND
                        `item_orders`.`is_active` = :is_active
                    GROUP BY `item_orders`.`item_id`
                    ORDER BY `item_orders`.`id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':temporary_name', trim($_POST['temporary_name']));
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> execute();
            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT
                        `name`,
                        SUM(`quantity`) AS `quantity`,
                        `price`,
                        `table_id`,
                        `user_id`,
                        `temporary_name`
                    FROM `menu_items`, `users`, `item_orders`
                    WHERE
                        `table_id` = (SELECT `table_id` FROM `restaurant_tables`, `table_users` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `is_active` = :is_active ORDER BY `updated_at` DESC LIMIT 1) AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `user_id` = (SELECT `user_id` FROM `restaurant_tables`, `table_users` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `is_active` = :is_active ORDER BY `updated_at` DESC LIMIT 1) AND `restaurant_tables`.`id` = `table_users`.`table_id` AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active ORDER BY `table_users`.`id` DESC LIMIT 1) AND
                        `menu_items`.`id` = `item_orders`.`item_id` AND
                        `users`.`id` = `item_orders`.`user_id` AND
                        `delivered` = :delivered AND
                        `item_orders`.`is_active` = :is_active
                    GROUP BY `item_orders`.`item_id`
                    ORDER BY `item_orders`.`id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 1);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> execute();
            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        }

        // get variables
        $table_id = $row[0]['table_id'] ?? $_POST['table_id'] ?? '';
        $user_id = $row[0]['user_id'] ?? $_POST['user_id'] ?? '';
        $temporary_name = $row[0]['temporary_name'] ?? $_POST['temporary_name'] ?? '';
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    //////// clicked paid button /////////
    try {
        if(isset($_POST['paid-btn'])) {
            include("../templates/receipt.php"); // included receipt file
            echo ('
                <script src="/js/jspdf.umd.min.js"></script>
                <script src="/js/html2canvas.min.js"></script>
                <script src="/js/save.pdf.js"></script>
                <script>saveDivAsPDF()</script>
            ');

            // update records
            $sql = "UPDATE `item_orders` SET `is_active` = :is_active WHERE `table_id` = :table_id AND `user_id` = :user_id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 0);
            $stmt -> bindValue(':table_id', trim($_POST['table_id']));
            $stmt -> bindValue(':user_id', trim($_POST['user_id']));
            $stmt -> execute();

            $sql = "UPDATE `table_users` SET `is_active` = :is_active WHERE `user_id` = :user_id AND `table_id` = :table_id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':is_active', 0);
            $stmt -> bindValue(':user_id', trim($_POST['user_id']));
            $stmt -> bindValue(':table_id', trim($_POST['table_id']));
            $stmt -> execute();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lavor Hub Restaurant - Add Restaurant Tables</title>
    <link rel="icon" href="/img/logo.jpg">
    <link rel="stylesheet" href="/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/add_pages.css?v=<?= time(); ?>">
    <style>
        .table-container td form button[type="submit"] {
            width: 100%;
            height: auto;
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .table-container td form button[type="submit"]:hover {
            background-color: #d35400;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <header>
        <h1>🍽️ Lavor Hub Restaurant 🍴</h1>
        <address>📍 160A /1.Dehiwala Road, Maharagama.</address>
    </header>
    <main>
        <div class="main-header">
            <h3>💻 Cashier 🪙</h3>
            <p>📠 Computer IP Address - <?= htmlspecialchars($remote_addr) ?></p>
        </div>
        <div class="main-content">
            <form action="/cashier" method="post">
                <fieldset>
                    <label>Customer Table Number:<input type="number" name="table_id" id="table_id" value="<?= htmlspecialchars($table_id) ?>" placeholder="Table ID" min="1" max="100"></label>
                    <label>Customer ID Number:<input type="number" name="user_id" id="user_id" value="<?= htmlspecialchars($user_id) ?>" placeholder="User ID" min="1" max="100"></label>
                    <label>Customer Temporary Name:<input type="text" name="temporary_name" id="temporary_name" value="<?= htmlspecialchars($temporary_name) ?>" placeholder="User Temporary Name" minlength="1" maxlength="100"></label>
                    <div class="form-actions">
                        <button type="submit">CHECKOUT</button>
                        <button type="button" onclick="clearAll()">CLEAR ALL</button>
                    </div>
                </fieldset>
            </form>
            <div class="table-container">
                <table>
                    <caption>Table - <?= htmlspecialchars($row[0]['table_id'] ?? '') ?> Checkout</caption>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Full Pirce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = 0 ?>
                        <?php foreach($row as $key => $value): ?>
                            <tr>
                                <td><?= htmlspecialchars($value['name']) ?></td>
                                <td><?= htmlspecialchars($value['quantity']) ?></td>
                                <td><?= htmlspecialchars(number_format($value['price'], 2)) ?></td>
                                <td><?= htmlspecialchars(number_format($value['price'] * $value['quantity'], 2)) ?></td>
                            </tr>
                            <?php $total = $total + ($value['price'] * $value['quantity']) ?>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3">Total</td>
                            <td><?= number_format($total, 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">No. of Items</td>
                            <td><?= count($row) ?></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <form action="/cashier" method="post">
                                    <input type="hidden" name="table_id" value="<?= htmlspecialchars($table_id) ?>">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
                                    <?php
                                        if(count($row) > 0) {
                                            ?><button type="submit" name="paid-btn" onclick="return confirm('Are you sure the customer you selected paid for items ?')" <?= (isset($_POST['paid-btn'])) ? 'style="display: none;"' : '' ?>>Pending Pay</button><?php
                                        }
                                    ?>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer>
        <h4>&copy; 2026 Lavor Hub Restaurant. All rights reserved.</h4>
    </footer>

    <!-- Js -->
    <script src="/js/clear_all_inputs.js"></script>
</body>
</html>
