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
                                    if ($value_4 == '04') {
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

    ////////////// find records in item order table ///////////////
    try {
        $sql = "SELECT *
                FROM `restaurant_tables`, `menu_items`, `item_orders`
                WHERE
                    `item_orders`.`table_id` = `restaurant_tables`.`id` AND
                    `item_orders`.`item_id` = `menu_items`.`id` AND
                    `restaurant_tables`.`table_section` = :table_section AND
                    `item_orders`.`delivered` = :delivered AND
                    `item_orders`.`is_active` = :is_active
                ORDER BY `item_orders`.`id` ASC";
        $stmt = $pdo -> prepare($sql);
        $stmt -> bindValue(':table_section', "bar");
        $stmt -> bindValue(':delivered', 0);
        $stmt -> bindValue(':is_active', 1);
        $stmt -> execute();
        $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    //////////// UPDATE item_orders TABLE ////////////
    try {
        if(isset($_POST['delivered'])) {
            $sql = "UPDATE `item_orders` SET `delivered` = :delivered WHERE `id` = :id";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':delivered', 1);
            $stmt -> bindValue(':id', trim($_POST['delivered']));
            $stmt -> execute();

            // redirect to page
            header('Location: /bar');
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
            <h3>💻 Bar Section 🍹</h3>
            <p>📠 Computer IP Address - <?= htmlspecialchars($remote_addr) ?></p>
        </div>
        <div class="main-content">
            <div class="table-container">
                <table>
                    <caption>Available Customer Orders</caption>
                    <thead>
                        <tr>
                            <th>Order No</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Order From (Table No)</th>
                            <th>Table Location</th>
                            <th>Ordered Time</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($row as $key => $value): ?>
                            <tr>
                                <td><?= htmlspecialchars($value['id']) ?></td>
                                <td><?= htmlspecialchars($value['name']) ?></td>
                                <td><?= htmlspecialchars($value['quantity']) ?></td>
                                <td><?= htmlspecialchars($value['table_id']) ?></td>
                                <td><?= htmlspecialchars($value['location'] ?? '') ?></td>
                                <td><?= htmlspecialchars($value['created_at']) ?></td>
                                <td>
                                    <form action="/bar" method="post">
                                        <button type="submit" name="delivered" value="<?= htmlspecialchars($value['id']) ?>"
                                            onclick="return confirm('Are you sure you prepared this item and ready to deliver item ?')">Pending Delivery</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer>
        <h4>&copy; 2026 Lavor Hub Restaurant. All rights reserved.</h4>
    </footer>
</body>
</html>
