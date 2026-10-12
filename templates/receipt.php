<?php date_default_timezone_set('Asia/Colombo') ?>
<?php $script_name = $_SERVER['SCRIPT_NAME'] ?>

<style>
    #pdf-content * {
        font-family: "Roboto", sans-serif;
    }
    #pdf-content {
        width: 360px;
        margin: auto;
        display: none;
    }
    #pdf-content img {
        display: block;
        width: 130px;
        margin-left: auto;
        margin-right: auto;
    }
    #pdf-content h1, #pdf-content h4, #pdf-content p {
        text-align: center;
    }
    #pdf-content table {
        width: 100%;
        border-collapse: collapse;
    }
    #pdf-content h1, #pdf-content h4 {
        margin: 0;
        padding: 0;
    }
    .data-tab {
        margin-top: 25px;
    }
    .data-tab table thead th {
        padding: 8px;
        border-top: 4px dashed black;
        border-bottom: 2px dashed black;
    }
    .data-tab table td.currency {
        text-align: right;
    }
    #item-qty {
        text-align: center;
    }
    #count-item {
        padding: 30px 0 20px 0;
        border-bottom: 2px dashed black;
        text-align: center;
    }
    #scale {
        font-size: larger;
        font-weight: bold;
    }
    #scale-name {
        padding-top: 10px;
        font-size: larger;
    }
    #end-line {
        padding-top: 20px;
        border-top: 3px dashed black;
    }
    .del-row td {
        padding-bottom: 15px;
    }
    .paid-mark {
        position: relative;
        bottom: 225px;
        left: 135px;
        width: 200px;
        border: 2px solid blue;
        text-align: center;
        color: blue;
        transform: rotate(-15deg);
    }
</style>

<div id="pdf-content">
    <img src="/img/logo.jpg" alt="Loading logo image...">
    <h1>Lavor Hub Restaurant</h1>
    <h4>160A /1.Dehiwala Road, Maharagama.</h4>
    <h4>Tel. 011 2160578 /011 2746965</h4>
    <div class="del-tab">
        <table>
            <tr>
                <td>InvNo</td>
                <td colspan="3">:<?= str_pad(1, 8, 0, STR_PAD_LEFT) ?></td>
            </tr>
            <tr>
                <td>Date</td>
                <td>:<?= date('d/m/y') ?></td>
                <td>Time</td>
                <td>:<?= date('h:i:s A') ?></td>
            </tr>
            <tr>
                <td>Cus.</td>
                <td colspan="3">:Name:- <?php echo ($temporary_name != '') ? htmlspecialchars($temporary_name) : ".............................." ?></td>
            </tr>
            <tr>
                <td>Staff.</td>
                <td>:<?= "None" ?></td>
                <td>Csh.</td>
                <td>:<?= ($script_name == '/staff/cashier.php') ? "admin" : "none" ?></td>
            </tr>
        </table>
    </div>
    <div class="data-tab">
        <table>
            <thead>
                <tr>
                    <th>Quantity</th>
                    <th>Price Quoted</th>
                    <th>Sum Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    try {
                        $outer_sql = "
                                        SELECT
                                            `name`,
                                            SUM(`quantity`) AS `quantity`,
                                            `price`
                                        FROM `menu_items`, `users`, `item_orders`
                                        WHERE
                                            `table_id` = (SELECT `table_id` FROM `restaurant_tables`, `table_users` WHERE `restaurant_tables`.`id` = `table_users`.`table_id` AND `restaurant_tables`.`id` = :table_id AND `restaurant_tables`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active) AND
                                            `user_id` = (SELECT `user_id` FROM `users`, `table_users` WHERE `users`.`id` = `table_users`.`user_id` AND `users`.`id` = :user_id AND `users`.`is_active` = :is_active AND `table_users`.`is_active` = :is_active) AND
                                            `menu_items`.`id` = `item_orders`.`item_id` AND
                                            `users`.`id` = `item_orders`.`user_id` AND
                                            `delivered` = :delivered AND
                                            `item_orders`.`is_active` = :is_active
                                        GROUP BY `item_orders`.`item_id`
                                        ORDER BY `item_orders`.`id` ASC
                        ";
                        $outer_stmt = $pdo -> prepare($outer_sql);
                        $outer_stmt -> bindValue(':table_id', $table_id);
                        $outer_stmt -> bindValue(':user_id', $user_id);
                        $outer_stmt -> bindValue(':is_active', 1);
                        ($script_name == '/cus/scan_qr.php') ? $outer_stmt -> bindValue(':delivered', 0) : (($script_name == '/staff/cashier.php') ? $outer_stmt -> bindValue(':delivered', 1) : '');
                        $outer_stmt -> execute();
                        $outer_row = $outer_stmt -> fetchAll(PDO::FETCH_ASSOC);

                        $total = 0;

                        foreach($outer_row as $key => $value) {
                            echo("<tr>");

                            echo('<td colspan="3" id="scale-name">');
                            echo($value['name']);
                            echo("</td>");

                            echo("</tr>");

                            echo('<tr class="del-row">');

                            echo('<td id="item-qty">');
                            echo($value['quantity']);
                            echo("</td>");

                            echo('<td class="currency">');
                            echo(number_format($value['price'], 2));
                            echo("</td>");

                            echo('<td class="currency">');
                            $total = $total + ($value['price'] * $value['quantity']);
                            echo(number_format(($value['price'] * $value['quantity']), 2));
                            echo("</td>");

                            echo("</tr>");
                        }

                        // count total
                        echo("<tr>");

                        echo('<td id="end-line"></td>');
                        echo('<td id="end-line">');
                        echo("Gross Total");
                        echo("</td>");

                        echo('<td class="currency" id="end-line">');
                        echo(number_format($total, 2));
                        echo("</td>");

                        echo("</tr>");

                        echo("<tr>");

                        echo("<td></td>");
                        echo('<td id="scale">');
                        echo("Net Total");
                        echo("</td>");

                        echo('<td class="currency" id="scale">');
                        echo(number_format($total, 2));
                        echo("</td>");

                        echo("</tr>");

                        echo("<tr>");

                        echo("<td></td>");
                        echo("<td>");
                        echo("Cash");
                        echo("</td>");

                        echo('<td class="currency">');
                        echo(number_format($total, 2));
                        echo("</td>");

                        echo("</tr>");

                        // count discount
                        echo("<tr>");

                        echo("<td></td>");
                        echo("<td>");
                        echo("Total Discount");
                        echo("</td>");

                        echo('<td class="currency">');
                        echo(number_format(0, 2));
                        echo("</td>");

                        echo("</tr>");

                        // count items
                        echo("<tr>");

                        echo('<td></td>');
                        echo('<td colspan="2">');
                        echo("No of Items : " . count($outer_row));
                        echo("</td>");

                        echo("</tr>");

                        // thank you
                        if($script_name == '/staff/cashier.php') {
                            echo("<tr>");

                            echo('<td colspan="3" id="count-item">');
                            echo("Thank You...");
                            echo("</td>");

                            echo("</tr>");
                        }
                    } catch(PDOException $e) {
                        die("Error: " . $e -> getMessage());
                    }
                ?>
            </tbody>
        </table>
    </div>
    <p>Website by KODI BRO (PVT) LTD - 0112 759 295</p>
    <?php if($script_name == '/staff/cashier.php'): ?>
        <div class="paid-mark">
            <h1>PAID</h1>
            <h4><?= strtoupper(date('d M Y')) ?></h4>
            <h3>LAVOR HUB RESTAURANT</h3>
        </div>
    <?php endif; ?>
</div>