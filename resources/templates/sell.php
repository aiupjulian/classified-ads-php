<?php
require_once(realpath(dirname(__FILE__) . "/../config.php"));
require_once(LIBRARY_PATH . "/databaseFunctions.php");
unset($error);
if (!isset($_SESSION['username'])) {
  header('location: index.php');
}

// edit ad
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
  $link;
  connect($link);
  $ad_id = mysqli_real_escape_string($link, $_GET['id']);
  $user_id = $_SESSION['id'];
  $query = "SELECT ad.*, user.username AS user_username, user.email AS user_email, user.phone AS user_phone, user.name AS user_name, city.name AS city_name,"
  . " state.name AS state_name, subcategory.name AS subcategory_name, category.name AS category_name, city.id AS city_id, subcategory.id AS subcategory_id"
  . " FROM ad INNER JOIN user ON ad.user_id = user.id"
  . " INNER JOIN city ON ad.city_id = city.id"
  . " INNER JOIN state ON city.state_id = state.id"
  . " INNER JOIN subcategory ON ad.subcategory_id = subcategory.id"
  . " INNER JOIN category ON subcategory.category_id = category.id WHERE ad.id='$ad_id' AND user.id='$user_id'";
  $adResult = mysqli_query($link, $query);
  $ad = mysqli_fetch_array($adResult, MYSQLI_ASSOC);
  $count = mysqli_num_rows($adResult);
  if ($count == 1) {
    $name = $ad['name'];
    $description = $ad['description'];
    $price = $ad['price'];
    $date = $ad['date'];
    $ad_user_username = $ad['user_username'];
    $ad_user_name = $ad['user_name'];
    $ad_user_phone = $ad['user_phone'];
    $ad_user_email = $ad['user_email'];
    $image = $ad['image'];
    $sold = $ad['sold'];
    $state = $ad['state_name'];
    $city_id = $ad['city_id'];
    $city = $ad['city_name'];
    $category = $ad['category_name'];
    $subcategory_id = $ad['subcategory_id'];
    $subcategory = $ad['subcategory_name'];
    mysqli_free_result($adResult);
    close($link);
  } else {
    header("location: error.php");
  }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $link;
  connect($link);

  // post parameters
  $name = mysqli_real_escape_string($link, $_POST['name']);
  $description = mysqli_real_escape_string($link, $_POST['description']);
  $price = mysqli_real_escape_string($link, $_POST['price']);
  $city_id = mysqli_real_escape_string($link, $_POST['city']);
  $subcategory_id = mysqli_real_escape_string($link, $_POST['subcategory']);
  // calculate current date
  $date_array = getdate();
  $date = $date_array['year'] . "-" . $date_array['mon'] . "-" . $date_array['mday'];
  // user_id from session
  $user_id = $_SESSION['id'];

  $file_name = $_FILES['image']['name'];
  $file_size = $_FILES['image']['size'];
  $file_tmp = $_FILES['image']['tmp_name'];
  $file_error = $_FILES['image']['error'];
  $exploded_file_name = explode('.', $file_name);
  $file_ext = strtolower(end($exploded_file_name));

  $extensions = array('jpeg', 'jpg', 'png');

  if (in_array($file_ext, $extensions) === false) {
     $error = 'Extension not allowed, please choose a JPEG or PNG file.';
  } else if ($file_error === 2 || $file_size > 2000000) {
     $error = 'File size must be less than 2 MB.';
  }

  if (!isset($error)) {
    global $config;
    $image_path = sha1_file($file_tmp) . $date . $date_array['seconds'] . "." . $file_ext;
    $path_to_upload = $config["paths"]["images"]["uploads"] . "/" . $image_path;
    if (move_uploaded_file($file_tmp, $path_to_upload)) {
      if (isset($_GET["id"])) {
        $ad_id = mysqli_real_escape_string($link, $_GET['id']);
        $query = "UPDATE ad SET name='$name', description='$description', price='$price', image='$image_path', city_id='$city_id', subcategory_id='$subcategory_id'"
          . " WHERE ad.id='$ad_id'";
          if (mysqli_query($link, $query)) {
            header("location: ad.php?id=" . $ad_id);
          } else {
            $error = "Error while trying to update ad.";
          }
      } else {
        $query = "INSERT INTO ad (name, description, price, date, user_id, image, city_id, subcategory_id)"
          . "VALUES ('$name', '$description', '$price', '$date', '$user_id', '$image_path', '$city_id', '$subcategory_id')";
        if (mysqli_query($link, $query)) {
          $ad_id = mysqli_insert_id($link);
          header("location: ad.php?id=" . $ad_id);
        } else {
          $error = "Error while trying to create ad.";
        }
      }
    } else {
      $error = 'There was a problem uploading the file.';
    }
  }
  close($link);
}
?>
<h2 class="form-title">Create Ad</h2>
<form action="" method="post" class="form" enctype="multipart/form-data">
  <label for="name">Name:</label>
  <input type="text" name="name" maxlength="15" required <?php if(isset($name)) echo "value='$name'"; ?>>
  <label for="description">Description:</label>
  <input type="text" name="description" maxlength="60" required <?php if(isset($description)) echo "value='$description'"; ?>>
  <label for="price">Price:</label>
  <input type="number" name="price" maxlength="11" required <?php if(isset($price)) echo "value='$price'"; ?>>
  <label for="city">City:</label>
  <select name="city">
    <?php
    $link;
    connect($link);
    $statesQuery = "SELECT * FROM state";
    $statesResult = mysqli_query($link, $statesQuery);
    while ($state = mysqli_fetch_array($statesResult, MYSQLI_ASSOC)) {
    ?>
      <optgroup label="<?php echo $state['name']; ?>">
      <?php
      $citiesQuery = "SELECT * FROM city where state_id=" . $state['id'];
      $citiesResult = mysqli_query($link, $citiesQuery);
      while ($city = mysqli_fetch_array($citiesResult, MYSQLI_ASSOC)) {
      ?>
        <option value=<?php echo $city['id']; ?> <?php if(isset($city_id) && $city['id'] == $city_id) echo 'selected="selected"'; ?>>
          <?php echo $city['name'] ?>
        </option>
      <?php } ?>
      </optgroup>
    <?php
    }
    close($link);
    ?>
  </select>
  <label for="subcategory">Subcategory:</label>
  <select name="subcategory">
    <?php
    $link;
    connect($link);
    $categoryQuery = "SELECT * FROM category";
    $categoryResult = mysqli_query($link, $categoryQuery);
    while ($category = mysqli_fetch_array($categoryResult, MYSQLI_ASSOC)) {
    ?>
      <optgroup label="<?php echo $category['name']; ?>">
      <?php
      $subcategoriesQuery = "SELECT * FROM subcategory where category_id=" . $category['id'];
      $subcategoriesResult = mysqli_query($link, $subcategoriesQuery);
      while ($subcategory = mysqli_fetch_array($subcategoriesResult, MYSQLI_ASSOC)) {
      ?>
        <option value=<?php echo $subcategory['id']; ?> <?php if(isset($subcategory_id) && $subcategory['id'] == $subcategory_id) echo 'selected="selected"'; ?>>
          <?php echo $subcategory['name'] ?>
        </option>
      <?php } ?>
      </optgroup>
    <?php
    }
    close($link);
    ?>
  </select>
  <label for="image">Image:</label>
  <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
  <input type="file" name="image" accept="image/png, image/jpeg">
  <button class="button">Submit</button>
  <?php if (isset($error)) { ?>
    <div class="error"><?php echo $error; ?></div>
  <?php } ?>
</form>
