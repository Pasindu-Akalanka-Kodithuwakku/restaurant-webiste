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
                                    if ($value_4 == '01') {
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
        if(isset($_POST['name']) && trim($_POST['name']) != '') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $type = trim($_POST['type']);
            $title = trim($_POST['title']);
            $category = trim($_POST['category']);
            $price = trim($_POST['price']);
            $image = basename(trim($_FILES['image']['name']));
            $menu_section = trim($_POST['menu_section']);
            $is_available = trim($_POST['is_available']);

            if (strlen($name) > 100 || !preg_match('/^[A-Za-z0-9 _&()-]+$/', $name)) {
                die("Invalid name.");
            }
            if (strlen($type) > 8 || !preg_match('/^[a-z]+$/', $type)) {
                die("Invalid type.");
            }
            if ($title !== '' && (strlen($title) > 50 || !preg_match('/^[A-Za-z ]+$/', $title))) {
                die("Invalid title.");
            }
            if ($category !== '' && (strlen($category) > 50 || !preg_match('/^[A-Za-z ]+$/', $category))) {
                die("Invalid category.");
            }
            if (strlen($price) > 9 || !preg_match('/^[0-9.]+$/', $price)) {
                die("Invalid price.");
            }
            if ($image !== '' && (strlen($image) > 255 || !preg_match('/^[A-Za-z0-9 _()\-]+\.[A-Za-z0-9]{3,4}$/', $image))) {
                die("Invalid image.");
            }
            if (strlen($menu_section) > 7 || !preg_match('/^[a-z]+$/', $menu_section)) {
                die("Invalid menu section.");
            }
            if (strlen($is_available) > 1 || !preg_match('/^[0-1]$/', $is_available)) {
                die("Invalid is available.");
            }

            //////////// creating customized sql command ////////////////
            $fields = ['name' => $name, 'type' => $type, 'price' => $price, 'menu_section' => $menu_section, 'is_available' => $is_available];

            // add optional fields if they are set
            if ($description !== '') $fields['description'] = $description;
            if ($title !== '') $fields['title'] = $title;
            if ($category !== '') $fields['category'] = $category;
            if ($image !== '') $fields['image'] = $image;

            // create dynamic field and placeholder strings
            $fieldNames = array_keys($fields); // ['name', 'price']
            $placeholders = array_map(fn($f) => ":$f", $fieldNames); // [':name', ':price']

            $sql = "INSERT INTO `menu_items`(" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo -> prepare($sql);

            // bind all params in a loop
            foreach ($fields as $field => $value) {
                $stmt->bindValue(":$field", $value);
            }

            $stmt -> execute();

            if($image !== '') {
                if (!is_dir(__DIR__. "/../img")) mkdir(__DIR__ . "/../img", 0777, true);
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . "/../img/" . $image);
            }

            header('Location: /addMenu'); // stored a menu item
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////////////// Update details //////////////////////////
    try {
        if(isset($_POST['update_records'])) {
            $fields_config = [
                'name' => ['regex' => '/^[A-Za-z0-9 _&()-]+$/', 'max' => 100, 'nullable' => false],
                'type' => ['regex' => '/^[a-z]+$/', 'max' => 8, 'nullable' => false],
                'title' => ['regex' => '/^[A-Za-z ]+$/', 'max' => 50, 'nullable' => true],
                'category' => ['regex' => '/^[A-Za-z ]+$/', 'max' => 50, 'nullable' => true],
                'price' => ['regex' => '/^[0-9.]+$/', 'max' => 9, 'nullable' => false],
                'menu_section' => ['regex' => '/^[a-z]+$/', 'max' => 7, 'nullable' => false],
                'is_available' => ['regex' => '/^[0-1]$/', 'max' => 1, 'nullable' => false]
            ];

            foreach ($_POST as $key => $value) {
                if ($key == 'update_records') continue;
            
                $item_id = substr(strstr($key, '-'), 1);
                $column = substr($key, 0, -(strlen($item_id) + 1));

                if($column != 'description') {
                    $config = $fields_config[$column];
                    $value = trim($value);
                
                    // validate input
                    if ($value === '' && $config['nullable']) {
                        $cell_value = NULL;
                    } else {
                        if (strlen($value) > $config['max'] || !preg_match($config['regex'], $value)) die("Invalid $column.");
                        $cell_value = $value;
                    }
                } else {
                    $value = trim($value);

                    // validate input
                    if($value === '') {
                        $cell_value = NULL;
                    } else {
                        $cell_value = $value;
                    }
                }
            
                // build and run query
                $sql = "UPDATE `menu_items` SET `$column` = :cell_value WHERE `id` = :item_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':cell_value', $cell_value);
                $stmt->bindValue(':item_id', $item_id);
                $stmt->execute();
            }            

            //////////// IMAGES UPDATE ////////////
            foreach($_FILES as $key => $value) {
                $item_id = substr(strstr($key, '-'), 1);
                $image = basename(trim($_FILES["$key"]['name']));

                if ($image !== '') {
                    if(strlen($image) > 255 || !preg_match('/^[A-Za-z0-9 _()\-]+\.[A-Za-z0-9]{3,4}$/', $image)) {
                        die("Invalid image.");
                    }
                    $sql = "UPDATE `menu_items` SET `image` = :cell_value WHERE `id` = :item_id";
                    $stmt = $pdo -> prepare($sql);
                    $stmt -> bindValue(':cell_value', $image);
                    $stmt -> bindValue(':item_id', $item_id);
                    $stmt -> execute();

                    if (!is_dir(__DIR__ . "/../img")) mkdir(__DIR__ . "/../img", 0777, true);
                    move_uploaded_file($_FILES["$key"]['tmp_name'], __DIR__ . "/../img/" . $image);
                }
            }

            // updated successfully
            header('Location: /addMenu');
            exit();
        }
    } catch(PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    ////////// check is set filter button /////////////
    try {
        if(isset($_REQUEST['menu_section'])) {
            $menu_section = trim($_REQUEST['menu_section']);
            
            $sql = "SELECT * FROM `menu_items` WHERE `menu_section` = :menu_section ORDER BY `id` ASC";
            $stmt = $pdo -> prepare($sql);
            $stmt -> bindValue(':menu_section', $menu_section);
        } else {
            $sql = "SELECT * FROM `menu_items` ORDER BY `id` DESC";
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
            <h3>💻 Manage Menu Items 📖</h3>
            <p>📠 Computer IP Address - <?= htmlspecialchars($remote_addr) ?></p>
        </div>
        <div class="main-content">
            <form action="/addMenu" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>NEW +</legend>
                    <label>Name* :<input type="text" name="name" id="" maxlength="100" placeholder="Name (e.g. Chicken Sambal, Plain Rice, etc.)" required></label>
                    <label>Description (Optional):<textarea name="description" id="" placeholder="Description (e.g. Ingredients, etc.)"></textarea></label>
                    <label>Type:<select name="type" id=""><option value="food">Food</option><option value="beverage">Beverage</option></select></label>
                    <label>Title* :<input type="text" name="title" id="" maxlength="50" placeholder="Title (e.g. Bites Menu, Indian Menu, etc.)" required></label>
                    <label>Category* :<input type="text" name="category" id="" maxlength="50" placeholder="Category (e.g. Chicken, Muttion, etc.)" required></label>
                    <label>Price* :<input type="text" name="price" id="" maxlength="9" placeholder="Price (e.g. 1400.00)" required></label>
                    <label>Image (Optional):<input type="file" name="image" id="" accept="image/*"></label>
                    <label>Menu Section:<select name="menu_section" id=""><option value="both">Both (Kitchen & Bar)</option><option value="kitchen">Kitchen</option><option value="bar">Bar</option></select></label>
                    <label>Is Available:<select name="is_available" id=""><option value="1">1</option><option value="0">0</option></select></label>
                    <div class="form-actions">
                        <button type="submit" onclick="return confirm('Are you sure you want to save new menu item details ?')">SAVE NEW MENU ITEM</button>
                        <button type="reset">RESET ALL</button>
                    </div>
                </fieldset>
            </form>
            <form action="/addMenu" method="post" enctype="multipart/form-data">
                <fieldset>
                    <legend>UPDATE ▲</legend>
                    <a href="/addMenu/showKitchen"><button type="button">Show Kitchen Menu Items</button></a>
                    <a href="/addMenu/showBar"><button type="button">Show Bar Menu Items</button></a>
                    <button type="submit" name="update_records" onclick="return confirm('Are you sure you want to update last changes ?')">UPDATE LAST CHANGES (IF ANY) ☼</button>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Price (LKR)</th>
                                    <th>Image</th>
                                    <th>Menu Section</th>
                                    <th>Is Active</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($row as $data): ?>
                                    <tr>
                                        <td><input type="number" name="" id="" value="<?= htmlspecialchars($data['id']) ?>" readonly></td>
                                        <td><input type="text" name="name-<?=$data['id']?>" id="" value="<?= htmlspecialchars($data['name']) ?>" maxlength="100" placeholder="Name (e.g. Chicken Sambal, Plain Rice, etc.)" required></td>
                                        <td><textarea name="description-<?=$data['id']?>" id="" placeholder="Description (e.g. Ingredients, etc.)"><?= htmlspecialchars($data['description'] ?? '') ?></textarea></td>
                                        <td><select name="type-<?=$data['id']?>" id=""><option value="food" <?= ($data['type'] == "food") ? "selected" : '' ?>>Food</option><option value="beverage" <?= ($data['type'] == "beverage") ? "selected" : '' ?>>Beverage</option></select></td>
                                        <td><input type="text" name="title-<?=$data['id']?>" id="" value="<?= htmlspecialchars($data['title'] ?? '') ?>" maxlength="50" placeholder="Title (e.g. Bites Menu, Indian Menu, etc.)" required></td>
                                        <td><input type="text" name="category-<?=$data['id']?>" id="" value="<?= htmlspecialchars($data['category'] ?? '') ?>" maxlength="50" placeholder="Category (e.g. Chicken, Muttion, etc.)" required></td>
                                        <td><input type="text" name="price-<?=$data['id']?>" id="" value="<?= htmlspecialchars($data['price']) ?>" maxlength="9" placeholder="Price (e.g. 1400.00)" required></td>
                                        <td><img src="/img/<?= htmlspecialchars($data['image'] ?? '') ?>" alt="<?= htmlspecialchars($data['image'] ?? '') ?>"><input type="file" name="image-<?=$data['id']?>" id="" accept="image/*"></td>
                                        <td><select name="menu_section-<?=$data['id']?>" id=""><option value="both" <?= ($data['menu_section'] == "both") ? "selected" : '' ?>>Both (Kitchen & Bar)</option><option value="kitchen" <?= ($data['menu_section'] == "kitchen") ? "selected" : '' ?>>Kitchen</option><option value="bar" <?= ($data['menu_section'] == "bar") ? "selected" : '' ?>>Bar</option></select></td>
                                        <td><select name="is_available-<?=$data['id']?>" id=""><option value="1" <?= ($data['is_available'] == 1) ? "selected" : '' ?>>1</option><option value="0" <?= ($data['is_available'] == 0) ? "selected" : '' ?>>0</option></select></td>
                                        <td><input type="datetime-local" name="" id="" value="<?= htmlspecialchars($data['created_at']) ?>"></td>
                                        <td><input type="datetime-local" name="" id="" value="<?= htmlspecialchars($data['updated_at']) ?>"></td>
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
</body>
</html>
