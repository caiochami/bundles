<?php
session_start();

$name = $_SESSION["input"]["name"] ?? null;
$email = $_SESSION["input"]["email"] ?? null;
$age = $_SESSION["input"]["age"] ?? null;
$gender = $_SESSION["input"]["gender"] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Testing</title>
  <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet" />
</head>

<body class="p-6">
  <?php
  if (isset($_SESSION["errors"])) { ?>

    <div role="alert">
      <div class="bg-red-500 text-white font-bold rounded-t px-4 py-2">
        Errors found:
      </div>
      <div class="border border-t-0 border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700">
        <?php foreach ($_SESSION["errors"] as $field => $errors) { ?>

          <p>
            <?= $field ?>: <ul>
              <?php foreach ($errors as $error) {
                echo "<li>" . $error . "</li>";
              } ?>
            </ul>
          </p>

        <?php } ?>
      </div>
    </div>

  <?php } ?>

  <form action="handle.php" method="POST" class="max-w-lg mt-2">
    <div class="flex flex-wrap -mx-3 mb-6">
      <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-first-name">
          Nome
        </label>
        <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-red-500 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white" id="grid-first-name" type="text" name="name" value="<?= $name ?>" placeholder="Jane" />
        <p class="text-red-500 text-xs italic">Please fill out this field.</p>
      </div>
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-last-name">
          Email
        </label>
        <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-last-name" type="text" name="email" value="<?= $email ?>" placeholder="Doe" />
      </div>
    </div>
    <div class="flex flex-wrap -mx-3 mb-6">
      <div class="w-full px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-password">
          Password
        </label>
        <input name="password" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-password" type="password" placeholder="******************" />
      </div>
    </div>
    <div class="flex flex-wrap -mx-3 mb-6">
      <div class="w-full px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-password">
          Password Confirmation
        </label>
        <input class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-password" type="password" name="password_confirmation" placeholder="******************" />
      </div>
    </div>
    <div class="flex flex-wrap -mx-3 mb-2">
      <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-city">
          Age
        </label>
        <input value="<?= $age ?>" name="age" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-city" type="number" placeholder="Age" />
      </div>
      <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-state">
          Gender
        </label>
        <div class="relative">
          <select name="gender" class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-state">
            <option <?php if ($gender === "male") echo "selected"; ?> value="male">Male</option>
            <option <?php if ($gender === "female") echo "selected"; ?> value="female">Female</option>
            <option value="undefined">Undefined</option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
              <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
            </svg>
          </div>
        </div>
      </div>
      <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
        <button class="px-4 py-2 bg-blue-500 text-white mt-6" type="submit">
          Enviar
        </button>
      </div>
    </div>
  </form>
</body>

</html>