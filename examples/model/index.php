<?php

use Bundles\DatabaseConnection;

session_start();

require "../../vendor/autoload.php";

include "./connection.php";
include "./User.php";

$connection = DatabaseConnection::attempt($db_params);

$users = User::all($connection);

$action = $_GET["action"] ?? "store";
$id = $_GET["id"] ?? null;
$user = null;

if ($action === "update" && isset($id)) {
  $user = User::find($connection, $id);
}

$name = $user->name ?? $_SESSION["input"]["name"] ?? null;
$email = $user->email ?? $_SESSION["input"]["email"] ?? null;
$age = $user->age ?? $_SESSION["input"]["age"] ?? null;
$gender = $user->gender ?? $_SESSION["input"]["gender"] ?? null;
$birthday = $user->birthday ?? $_SESSION["input"]["birthday"] ?? null;

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
  <div class="flex flex-col space-y-2 justify-center">


    <div class="flex space-x-4 justify-center">

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

      <form action="handle.php" method="POST" class="max-w-lg  mt-2">
        <input type="hidden" name="action" value="<?= $action ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
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
            <div class="w-full px-3">
              <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-password">
                Birthday
              </label>
              <input 
              value="<?=$birthday?>"
              name="birthday" class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" id="grid-birthday" type="date" />
            </div>
          </div>
          <div class="w-full px-3 mb-6 md:mb-0">
            <button class="px-4 py-2 bg-blue-500 text-white mt-6" type="submit">
              Enviar
            </button>
          </div>
        </div>
      </form>
    </div>


    <table class="table-auto">
      <thead>
        <tr>
          <th class="px-4 py-2">Id</th>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">E-Mail</th>
          <th class="px-4 py-2">Age</th>
          <th class="px-4 py-2">Gender</th>
          <th class="px-4 py-2">Created At</th>
          <th class="px-4 py-2">Updated At</th>
          <th class="px-4 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$users->count()) { ?>
          <tr>
            <td class="border px-4 py-2 ">No data found</td>
          </tr> <?php } ?>
        <?php foreach ($users->get() as $user) { ?>
          <tr>
            <td class="border px-4 py-2"><?= $user->id ?></td>
            <td class="border px-4 py-2"><?= $user->name ?></td>
            <td class="border px-4 py-2"><?= $user->email ?></td>
            <td class="border px-4 py-2"><?= $user->age ?></td>
            <td class="border px-4 py-2"><?= $user->gender ?></td>
            <td class="border px-4 py-2"><?= $user->created_at ?></td>
            <td class="border px-4 py-2"><?= $user->updated_at ?></td>
            <td class="border px-4 py-2">
              <a class="text-sm px-2 py-1 bg-teal-400 text-white" href="?id=<?= $user->id ?>&action=update">Editar</a>
              <a class="text-sm px-2 py-1 bg-red-400 text-white" href="destroy.php?id=<?= $user->id ?>&action=delete">Remover</a></td>

          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</body>

</html>