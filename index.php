<?php
require "vendor/autoload.php";
require_once "app/Product.php";
require_once "app/Warehouse.php";

use App\Product;
use App\Warehouse;
use LucidFrame\Console\ConsoleTable;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;

function isInputValid(int $userInput, int $minValue, int $maxValue): bool
{
    return $userInput >= $minValue && $userInput <= $maxValue;
}

$consoleColor = new ConsoleColor();
$warehouse = new Warehouse();

if (file_exists("data/logs.json")) {
    $oldLogs = json_decode(file_get_contents("data/logs.json"));
    $warehouse->updateLogs($oldLogs);
}

if (file_exists("data/storedProducts.json")) {
    $products = json_decode(file_get_contents("data/storedProducts.json"));
        foreach ($products as $product) {
            $warehouse->addProduct($product);
        }
}

//$users = json_decode(file_get_contents("users.json"));

$user = 'dude';
//$userIsValid = false;
//do {
//    echo "Please login.\n";
//    $userName = readline("Username: ");
//    foreach ($users as $user) {
//        if ($user->username === $userName) {
//            $userIsValid = true;
//        }
//    }
//    if (!$userIsValid) {
//        readline("Wrong username. Press any key to continue...");
//        $userIsValid = false;
//    } else {
//        $password = readline("Password: ");
//        foreach ($users as $user) {
//            if ($user->password === $password) {
//                $userIsValid = true;
//            }
//        }
//    }
//} while ($userIsValid === false);

do {
    //echo "\nLogged in as $userName.\n";
    $table = new ConsoleTable();
    $table
        ->addHeader("Index")
        ->addHeader("Id")
        ->addHeader("Name")
        ->addHeader("Units")
        ->addHeader("Created at")
        ->addHeader("Last updated at")
        ->setPadding(2)
        ->addRow();
    if ($warehouse->products()) {
        foreach ($warehouse->products() as $index => $product) {
            $table
                ->addColumn($index + 1 . ".", 0, $index)
                ->addColumn($product->getId(), 1, $index)
                ->addColumn($product->getName(), 2, $index)
                ->addColumn($product->getUnits(), 3, $index)
                ->addColumn($product->getCreatedAt(), 4, $index)
                ->addColumn($product->getUpdatedAt(), 5, $index);
            if ($product->getUnits() === 0) {
                $table->addColumn(
                    $consoleColor->apply("color_160", $product->getUnits()),
                    3,
                    $index
                );
            }
        }
    }
    $table->display();

    echo "\n1. Add product.\n";
    if ($warehouse->products()) {
        echo "2. Withdraw product.\n" .
            "3. Change product name.\n" .
            "4. Change product unit amount.\n" .
            "5. Remove product.\n";
    } else {
        echo $consoleColor->apply(
            "color_240",
            "2. Withdraw product.\n" .
            "3. Change product name.\n" .
            "4. Change product unit amount.\n" .
            "5. Remove product.\n"
        );
    }
    echo "6. View logs.\n" .
         "7. Exit.\n";

    $mainMenuChoice = (int) readline("Main Menu Choice: ");
    switch ($mainMenuChoice) {
        case 1:
            $productName = readline("Product name: ");
            $productInStock = (int) readline("Units in stock: ");
            if ($productInStock <= 0) {
                readline(
                    "Invalid input. Amount of units in stock cant be less than 1. Press any key to continue..."
                );
                break;
            }
            $warehouse->addProduct(new Product($productName, $productInStock));
            $newestProduct = $warehouse->products()[count($warehouse->products()) - 1];
            $warehouse->createLog(
                $newestProduct->getCreatedAt(),
                $newestProduct->getId() ,
                $user,
                "added [" .
                $productInStock .
                "] units of the new product: [" .
                $productName .
                "]"
            );
            break;
        case 2:
            $productChoice = (int) readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $withdrawAmount = (int) readline("Withdraw Amount: ");
            if (
                !isInputValid(
                    $withdrawAmount,
                    0,
                    $warehouse->products()[$productChoice - 1]->getUnits()
                )
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $selectedProduct->withdrawUnits($withdrawAmount);
            $selectedProduct->update();
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId() ,
                $user,
                "withdrew [" .
                $withdrawAmount .
                "] units from product: [" .
                $selectedProduct->getName() .
                "]"
            );
            break;
        case 3:
            $productChoice = (int) readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $productName = readline("Product name: ");
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $previousProductName = $selectedProduct->getName();
            $selectedProduct->setName($productName);
            $selectedProduct->update();
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId() ,
                $user,
                "changed the name of the product: [" .
                $previousProductName .
                "] to a new name: [" .
                $productName .
                "]"
            );
            break;
        case 4:
            $productChoice = (int) readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $productInStock = (int) readline("Units in stock: ");
            if ($productInStock <= 0) {
                readline(
                    "Invalid input. Amount of units in stock cant be less than 1. Press any key to continue..."
                );
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $previousUnitAmount = $selectedProduct->getUnits();
            $selectedProduct->setUnits($productInStock);
            $selectedProduct->update();
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId() ,
                $user,
                "changed product: [" .
                $selectedProduct->getName() .
                "] units from [" .
                $previousUnitAmount .
                "] to [" .
                $productInStock .
                "]"
            );
            break;
        case 5:
            $productChoice = (int) readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $selectedProduct->update();
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId() ,
                $user,
                "removed product: [" .
                $selectedProduct->getName() .
                "]"
            );
            $warehouse->removeProduct($productChoice - 1);
            break;
        case 6:
            $warehouse->viewLogs();
            readline("Press any key to continue...");
            break;
        case 7:
            $savedLogs = json_encode($warehouse->getLogs());
            file_put_contents("data/logs.json", $savedLogs);
            $storedProducts = $warehouse->jsonSerialize();
            file_put_contents("data/storedProducts.json", $storedProducts);
            return false;
        default:
            readline("Invalid input. Press any key to continue...");
    }
} while (true);
