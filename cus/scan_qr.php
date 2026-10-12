<?php
    // include files
    require_once('../config/connection.php');
    session_start();
    set_time_limit(310);

    // getting values...
    $message_box = array();
    $remote_addr = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // check login
    try {
        // getting user id by running it twice
        for($i=0; $i < 2; $i++) {
            $sql = "SELECT * FROM `users` WHERE `remote_address` = :remote_addr AND `user_agent` = :user_agent LIMIT 1";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':remote_addr', $remote_addr);
            $stmt -> bindValue(':user_agent', $user_agent);
            $stmt -> execute();
            $row = $stmt -> fetch(PDO::FETCH_ASSOC);

            // check result
            if($row) {
                if($row['is_active'] == 0) {
                    $forbidden = "Access denied, because you're not active customer. Please contact staff.";
                    include("../errors/403.php");
                    exit();
                }
                $user_id = $row['id']; // catch the user ID
                $temporary_name = $row['temporary_name']; // catch the user temp name (if anys)
                break;
            } else {
                $sql = "INSERT INTO `users`(`remote_address`, `user_agent`) VALUES (:remote_addr, :user_agent)";
                $stmt = $pdo -> prepare($sql);
                $stmt -> bindValue(':remote_addr', $remote_addr);
                $stmt -> bindValue(':user_agent', $user_agent);
                $stmt -> execute();
            }
        }

        // check remote address
        if(!str_starts_with($remote_addr, '192.168.')) {
            $forbidden = "Access restricted to local users.";
            include("../errors/403.php");
            exit();
        }

        /////////////////////////// check login options /////////////////////////
        if(isset($_REQUEST['table_number'])) {
            $sql = "SELECT * FROM `restaurant_tables` WHERE `table_number` = :table_number AND `is_active` = :is_active LIMIT 1";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':table_number', trim($_REQUEST['table_number']));
            $stmt -> bindValue(':is_active', 1);
            $stmt -> execute();
            $row = $stmt -> fetch(PDO::FETCH_ASSOC);

            // check result
            if($row) {
                $table_id = $row['id']; // restaurant table ID

                //check is table reserved one
                $sql = "SELECT * FROM `table_users` WHERE `table_id` = :table_id AND `is_active` = :is_active LIMIT 1";
                $stmt = $pdo -> prepare($sql);
                $stmt -> bindValue(':table_id', $table_id);
                $stmt -> bindValue(':is_active', 1);
                $stmt -> execute();
                $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                // check result
                if($row) {
                    if($row['user_id'] != $user_id) {
                        $sql = "SELECT * FROM `users` WHERE `id` = :user_id AND `is_active` = :is_active LIMIT 1";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':user_id', $row['user_id']);
                        $stmt -> bindValue(':is_active', 1);
                        $stmt -> execute();
                        $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                        // check the table reserved, but user is not active or not
                        if($row) {
                            $forbidden = "Access denied, because the table you scanned already reserved one.";
                            include("../errors/403.php");
                            exit();
                        } else {
                            $sql = "UPDATE `table_users` SET `is_active` = :is_active WHERE `user_id` = :user_id";
                            $stmt = $pdo -> prepare($sql);
                            $stmt -> bindValue(':is_active', 0);
                            $stmt -> bindValue(':user_id', $row['id']);
                            $stmt -> execute();
                        }
                    } else {
                        $sql = "SELECT * FROM `table_users` WHERE `table_id` != :table_id AND `user_id` = :user_id AND `is_active` = :is_active LIMIT 1";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':table_id', $table_id);
                        $stmt -> bindValue(':user_id', $user_id);
                        $stmt -> bindValue(':is_active', 1);
                        $stmt -> execute();
                        $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                        // check about user
                        if($row) {
                            $forbidden = "Access denied, because you're already reserved other table.";
                            include("../errors/403.php");
                            exit();
                        }

                        // update last login
                        $sql = "UPDATE `table_users` SET `updated_at` = CURRENT_TIMESTAMP WHERE `user_id` = :user_id AND `table_id` = :table_id AND `is_active` = :is_active";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':user_id', $user_id);
                        $stmt -> bindValue(':table_id', $table_id);
                        $stmt -> bindValue(':is_active', 1);
                        $stmt -> execute();

                        // login permission successfully without going to the lobby
                        $_SESSION['lavorhub_restaurant']['access']['table_number'] = trim($_REQUEST['table_number']);
                        header('Location: /welcome');
                        exit();
                    }
                }

                // user add to the lobby
                $sql = "SELECT * FROM `table_users` WHERE `user_id` = :user_id AND `is_active` = :is_active LIMIT 1";
                $stmt = $pdo -> prepare($sql);
                $stmt -> bindValue(':user_id', $user_id);
                $stmt -> bindValue(':is_active', 2);
                $stmt -> execute();
                $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                if($row == FALSE) {
                    $sql = "INSERT INTO `table_users`(`user_id`, `table_id`, `is_active`) VALUES (:user_id, :table_id, :is_active)";
                    $stmt = $pdo -> prepare($sql);
                    $stmt -> bindValue(':user_id', $user_id);
                    $stmt -> bindValue(':table_id', $table_id);
                    $stmt -> bindValue(':is_active', 2);
                    $stmt -> execute();
                }

                ///////////////////////// start while of the lobby ///////////////////////
                $start_time = time();
                $timeout = 300; // seconds

                while(true) {
                    if((time() - $start_time) > $timeout) {
                        $forbidden = "Request timed out. Please refresh the page again.";
                        include("../errors/403.php");
                        exit();
                        break;
                    }

                    // searching having a record in lobby
                    $sql = "SELECT * FROM `table_users` WHERE `user_id` = :user_id AND `is_active` = :is_active LIMIT 1";
                    $stmt = $pdo -> prepare($sql);
                    $stmt -> bindValue(':user_id', $user_id);
                    $stmt -> bindValue(':is_active', 2);
                    $stmt -> execute();
                    $row = $stmt -> fetch(PDO::FETCH_ASSOC);
        
                    if($row == FALSE) {
                        $sql = "SELECT * FROM `table_users` WHERE `user_id` = :user_id AND `table_id` = :table_id AND `is_active` = :is_active LIMIT 1";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':user_id', $user_id);
                        $stmt -> bindValue(':table_id', $table_id);
                        $stmt -> bindValue(':is_active', 1);
                        $stmt -> execute();
                        $row = $stmt -> fetch(PDO::FETCH_ASSOC);
    
                        if($row) {
                            // login permission successfully
                            $_SESSION['lavorhub_restaurant']['access']['table_number'] = trim($_REQUEST['table_number']);
                            header('Location: /welcome');
                            exit();
                            break;
                        } else {
                            $forbidden = "Access denied, because staff couldn't given opportunity for you. Please find the another table or contact staff.";
                            include("../errors/403.php");
                            exit();
                            break;
                        }
                    } else {
                        sleep(1); // wait 1 sec
                        continue; // continue loop
                    }
                }
            } else {
                $forbidden = "Access denied, because invalid table number or the table you scanned temporary not worked.";
                include("../errors/403.php");
                exit();
            }
        } else if(isset($_SESSION['lavorhub_restaurant']['access']['table_number'])) {
            $sql = "SELECT
                        `table_users`.`id` AS `table_user_id`,
                        `table_users`.`user_id`,
                        `table_users`.`table_id`,
                        `table_users`.`is_active` AS `table_user_is_active`,
                        `users`.`is_active` AS `user_is_active`,
                        `table_section`,
                        `restaurant_tables`.`is_active` AS `restaurant_table_is_active`
                    FROM `table_users`, `users`, `restaurant_tables`
                    WHERE
                        `table_users`.`user_id` = `users`.`id` AND
                        `table_users`.`table_id` = `restaurant_tables`.`id` AND
                        `table_users`.`user_id` = :user_id AND
                        `table_users`.`is_active` = :table_user_is_active AND
                        `users`.`is_active` = :user_is_active AND
                        `restaurant_tables`.`table_number` = :table_number AND
                        `restaurant_tables`.`is_active` = :restaurant_table_is_active
                    LIMIT 1";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':user_id', $user_id);
            $stmt -> bindValue(':table_user_is_active', 1);
            $stmt -> bindValue(':user_is_active', 1);
            $stmt -> bindValue(':table_number', trim($_SESSION['lavorhub_restaurant']['access']['table_number']));
            $stmt -> bindValue(':restaurant_table_is_active', 1);
            $stmt -> execute();
            $row = $stmt -> fetch(PDO::FETCH_ASSOC);

            if($row == FALSE) {
                unset($_SESSION['lavorhub_restaurant']['access']['table_number']); // session destroy
                include("../errors/403.php");
                exit();
            }

            // required variables
            $table_id = $row['table_id'];
            $table_section = $row['table_section'];
        } else {
            include("../errors/403.php");
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ///////////////////////////// pressed order button //////////////////////////
    $isOrder = 0;

    if(isset($_POST['sendOrder'])) {
        foreach($_POST as $key => $value) {
            if($key != "sendOrder" && substr($key, -3) != "qty") {
                foreach($_POST as $outer_key => $outer_value) {
                    if(substr($outer_key, -3) == "qty" && substr($outer_key, 0, -4) == $key) {
                        $quantity = $outer_value; // set quantity
                        break;
                    }
                }

                // save order in database
                try {
                    $sql = "SELECT *
                            FROM `item_orders`
                            WHERE
                                `table_id` = :table_id AND
                                `item_id` = :item_id AND
                                `user_id` = :user_id AND
                                `quantity` = :quantity AND
                                `delivered` = :delivered AND
                                `is_active` = :is_active
                            LIMIT 1";
                    $stmt = $pdo -> prepare($sql);
                    $stmt -> bindValue(':table_id', $table_id);
                    $stmt -> bindValue(':item_id', $key);
                    $stmt -> bindValue(':user_id', $user_id);
                    $stmt -> bindValue(':quantity', $quantity);
                    $stmt -> bindValue(':delivered', 0);
                    $stmt -> bindValue(':is_active', 1);
                    $stmt -> execute();
                    $row = $stmt -> fetch(PDO::FETCH_ASSOC);

                    if($row) {
                        $message_box[] = ('
                            <script>
                                function notice() {
                                    alert("You have been ordered this already as same item & same quantity :(");
                                }
                                notice();
                            </script>
                        ');
                    } else {
                        $sql = "INSERT INTO `item_orders`(`table_id`, `item_id`, `user_id`, `quantity`) VALUES (:table_id, :item_id, :user_id, :quantity)";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':table_id', $table_id);
                        $stmt -> bindValue(':item_id', $key);
                        $stmt -> bindValue(':user_id', $user_id);
                        $stmt -> bindValue(':quantity', $quantity);
                        $stmt -> execute();

                        // saved successfully
                        $isOrder = 1;
                    }
                } catch(PDOException $e) {
                    die("Error: " . $e -> getMessage());
                }
            }
        }
        
        // display sent js alerts for customer
        if($isOrder == 1) {
            $message_box[] = ('
                <script>
                    function notice() {
                        alert("Your order sent successfully :)");
                    }
                    notice();
                </script>
            ');

            // download invoice PDF
            $message_box[] = ('
                <script src="/js/jspdf.umd.min.js"></script>
                <script src="/js/html2canvas.min.js"></script>
                <script src="/js/save.pdf.js"></script>
                <script>saveDivAsPDF()</script>
            ');
        } else {
            $message_box[] = ('
                <script>
                    function notice() {
                        alert("Nothing to order :(");
                    }
                    notice();
                </script>
            ');
        }
    }
?>

<!-- HTML --->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lavor Hub Restaurant - Menu Card</title>
    <link rel="icon" href="/img/logo.jpg">
    <link rel="stylesheet" href="/css/style.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>🍽️ Lavor Hub Restaurant 🍴</h1>
        <address>📍 160A /1.Dehiwala Road, Maharagama.</address>
    </header>
    <main>
        <div class="main-header">
            <h3>📖 Menu Card 📖</h3>
            <p>🖨️ Table No - <?= str_pad(htmlspecialchars($table_id), 3, 0, STR_PAD_LEFT) ?> 🔍</p>
        </div>
        <div class="main-content">
            <form action="/welcome" method="post" enctype="multipart/form-data">
                <div class="sticky-layout">
                    <?php
                        // Generate buttons for main categories
                        try {
                            $sql = "SELECT DISTINCT `title`
                                FROM `menu_items`
                                WHERE
                                    `is_available` = :is_available AND (
                                    `menu_section` = :menu_section_1 OR
                                    `menu_section` = :menu_section_2)
                                ORDER BY `id` ASC
                            ";
                            $stmt = $pdo -> prepare($sql);
                            $stmt -> bindValue(':is_available', 1);
                            $stmt -> bindValue(':menu_section_1', "both");
                            $stmt -> bindValue(':menu_section_2', $table_section);
                            $stmt -> execute();
                            $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);

                            foreach($row as $value) {
                                echo('<a href="#' .$value['title'] .'">' .$value['title'] .'</a>');
                            }
                        } catch(PDOException $e) {
                            die("Error: " . $e -> getMessage());
                        }
                    ?>
                    <h3>Total: <span id="total">0.00</span> LKR</h3>
                    <button type="submit" name="sendOrder" onclick="return confirm('Are you sure you want order selected ?')">Send Order ▲</button>
                    <button type="button" onclick="saveDivAsPDF()">Download My Order's Reception ▼</button>
                </div>
                <?php
                    // Display main sql records
                    try {
                        $sql = "SELECT DISTINCT `title`
                            FROM `menu_items`
                            WHERE
                                `is_available` = :is_available AND (
                                `menu_section` = :menu_section_1 OR
                                `menu_section` = :menu_section_2)
                            ORDER BY `id` ASC
                        ";
                        $stmt = $pdo -> prepare($sql);
                        $stmt -> bindValue(':is_available', 1);
                        $stmt -> bindValue(':menu_section_1', "both");
                        $stmt -> bindValue(':menu_section_2', $table_section);
                        $stmt -> execute();
                        $row = $stmt -> fetchAll(PDO::FETCH_ASSOC);

                        foreach($row as $key => $value) {
                            ?>
                            <div class="table-container">
                                <table>
                                    <caption id="<?= $value['title'] ?>"><?= htmlspecialchars($value['title'] ?? '') ?></caption>
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th></th>
                                            <th>Unit Price (LKR)</th>
                                            <th></th>
                                            <th>Quantity</th>
                                            <th>Price With Quantity (LKR)</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            # Display outer sql 1 records
                                            $title = $value['title'];

                                            $outer_sql_1 = "SELECT DISTINCT `category`
                                                FROM `menu_items`
                                                WHERE
                                                    `title` = :title AND
                                                    `is_available` = :is_available AND (
                                                    `menu_section` = :menu_section_1 OR
                                                    `menu_section` = :menu_section_2)
                                                ORDER BY `id` ASC
                                            ";
                                            $outer_stmt_1 = $pdo -> prepare($outer_sql_1);
                                            $outer_stmt_1 -> bindValue(':title', $title);
                                            $outer_stmt_1 -> bindValue(':is_available', 1);
                                            $outer_stmt_1 -> bindValue(':menu_section_1', "both");
                                            $outer_stmt_1 -> bindValue(':menu_section_2', $table_section);
                                            $outer_stmt_1 -> execute();
                                            $outer_row_1 = $outer_stmt_1 -> fetchAll(PDO::FETCH_ASSOC);

                                            foreach($outer_row_1 as $outer_key_1 => $outer_value_1) {
                                                ?>
                                                <tr>
                                                    <td colspan="7"><?= htmlspecialchars($outer_value_1['category'] ?? '') ?></td>
                                                </tr>
                                                <?php

                                                // Display outer sql 2 records
                                                $category = $outer_value_1['category'];
                                                
                                                $outer_sql_2 = "SELECT `id`, `description`, `name`, `price`, `image`
                                                    FROM `menu_items`
                                                    WHERE
                                                        `title` = :title AND
                                                        `category` = :category AND
                                                        `is_available` = :is_available AND (
                                                        `menu_section` = :menu_section_1 OR
                                                        `menu_section` = :menu_section_2)
                                                    ORDER BY `id` ASC
                                                ";
                                                $outer_stmt_2 = $pdo -> prepare($outer_sql_2);
                                                $outer_stmt_2 -> bindValue(':title', $title);
                                                $outer_stmt_2 -> bindValue(':category', $category);
                                                $outer_stmt_2 -> bindValue(':is_available', 1);
                                                $outer_stmt_2 -> bindValue(':menu_section_1', "both");
                                                $outer_stmt_2 -> bindValue(':menu_section_2', $table_section);
                                                $outer_stmt_2 -> execute();
                                                $outer_row_2 = $outer_stmt_2 -> fetchAll(PDO::FETCH_ASSOC);

                                                foreach($outer_row_2 as $outer_value_2) {
                                                    ?>
                                                    <tr>
                                                        <td><label for="<?= htmlspecialchars($outer_value_2['id']) ?>"><?= htmlspecialchars($outer_value_2['id']) ?></label></td>
                                                        <td><label for="<?= htmlspecialchars($outer_value_2['id']) ?>"><?= htmlspecialchars($outer_value_2['name']) ?></label></td>
                                                        <td><label for="<?= htmlspecialchars($outer_value_2['id']) ?>"><?= number_format(htmlspecialchars($outer_value_2['price']), 2) ?></label></td>
                                                        <td><input type="checkbox" name="<?= htmlspecialchars($outer_value_2['id']) ?>" id="<?= htmlspecialchars($outer_value_2['id']) ?>" value="<?= htmlspecialchars($outer_value_2['price']) ?>" onclick="handleCheckboxChange(event)" class="priceCheckbox"></td>
                                                        <td>
                                                            <button type="button" onclick="dec(event)">-</button>
                                                            <input type="number" name="<?= htmlspecialchars($outer_value_2['id']) . '-qty' ?>" value="1" min="1" max="20" readonly>
                                                            <button type="button" onclick="inc(event)" id="test">+</button>
                                                        </td>
                                                        <td><label for="<?= htmlspecialchars($outer_value_2['id']) ?>"><?= number_format(htmlspecialchars($outer_value_2['price']), 2) ?></label></td>
                                                        <td><a onclick='showDetails(<?= json_encode($outer_value_2["description"]); ?>, <?= json_encode($outer_value_2["image"]); ?>)'>View Details</a></td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                        }
                    } catch(PDOException $e) {
                        die("Error: " . $e -> getMessage());
                    }
                ?>
            </form>
        </div>
        <div id="popup">
            <span onclick="hidePopup()">Close ❌</span>
            <p id="popupContent"></p>
            <img src="" alt="Loading image..." id="item_image">
        </div>
        <?php
            include("../templates/receipt.php"); // included receipt template file
            if(!empty($message_box)) {
                foreach($message_box as $key => $value) {
                    echo ($value); // run js functions
                }
            }
        ?>
    </main>
    <footer>
        <h4>&copy; 2026 Lavor Hub Restaurant. All rights reserved.</h4>
    </footer>

    <!-- Js -->
    <script src="/js/amount.js"></script>
    <script src="/js/show.des.js"></script>
    <script src="/js/jspdf.umd.min.js"></script>
    <script src="/js/html2canvas.min.js"></script>
    <script src="/js/save.pdf.js"></script>
</body>
</html>
