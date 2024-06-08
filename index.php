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


$users = json_decode(file_get_contents("data/users.json"));

$userIsValid = false;
do {
    echo "Please login.\n";
    $user = readline("Username: ");
    foreach ($users as $userName) {
        if ($userName->username === $user) {
            $password = readline("Password: ");
            foreach ($users as $userPassword) {
                if ($userPassword->password === $password) {
                    $userIsValid = true;
                }
            }
        }
    }
    if ($userIsValid === false) {
        readline("Wrong password or username. Press any key to continue...");
    }
} while ($userIsValid === false);

do {
    echo "\nLogged in as $user.\n";
    $table = new ConsoleTable();
    $table
        ->addHeader("Index")
        ->addHeader("Id")
        ->addHeader("Name")
        ->addHeader("Units")
        ->addHeader("Price in EUR")
        ->addHeader("Quality expiration date")
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
                ->addColumn($product->getPrice(), 4, $index)
                ->addColumn($product->getExpirationDate(), 5, $index)
                ->addColumn($product->getCreatedAt(), 6, $index)
                ->addColumn($product->getUpdatedAt(), 7, $index);
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
            "5. Change product price.\n" .
            "6. Change product quality expiration date.\n" .
            "7. Remove product.\n" .
            "8. Create report.\n";
    } else {
        echo $consoleColor->apply(
            "color_240",
            "2. Withdraw product.\n" .
            "3. Change product name.\n" .
            "4. Change product unit amount.\n" .
            "5. Change product price.\n" .
            "6. Change product quality expiration date.\n" .
            "7. Remove product.\n" .
            "8. Create report.\n"
        );
    }
    echo "9. View logs.\n" .
        "10. Exit.\n";

    $mainMenuChoice = (int)readline("Main Menu Choice: ");
    switch ($mainMenuChoice) {
        case 1:
            $productName = readline("Product name: ");
            $productInStock = (int)readline("Units in stock: ");
            if ($productInStock <= 0) {
                readline(
                    "Invalid input. Amount of units in stock cant be less than 1. Press any key to continue..."
                );
                break;
            }
            $productPrice = (float)readline("Price: ");
            if ($productPrice <= 0) {
                readline(
                    "Invalid input. Product price cant be less than 0. Press any key to continue..."
                );
                break;
            }
            $productExpirationDate = strtolower(readline("Do you want to add a product quality expiration date? [y/n]: "));
            if ($productExpirationDate !== 'y' && $productExpirationDate !== 'n') {
                readline(
                    "Invalid input. Press any key to continue..."
                );
                break;
            }
            if ($productExpirationDate === "y") {
                echo "Use format [YYYY-MM-DD]\n";
                $productExpirationDate = readline("Enter product quality expiration date: ");
            }
            if ($productExpirationDate === "n") {
                $productExpirationDate = null;
            }
            $warehouse->addProduct(new Product($productName, $productInStock, $productPrice, $productExpirationDate));
            $newestProduct = $warehouse->products()[count($warehouse->products()) - 1];
            if ($productExpirationDate) {
                $warehouse->createLog(
                    $newestProduct->getCreatedAt(),
                    $newestProduct->getId(),
                    $user,
                    "added [" .
                    $productInStock .
                    "] units of the new product: [" .
                    $productName .
                    "] priced at [" .
                    $productPrice .
                    "] with quality expiration date [" .
                    $productExpirationDate .
                    "]"
                );
            } else {
                $warehouse->createLog(
                    $newestProduct->getCreatedAt(),
                    $newestProduct->getId(),
                    $user,
                    "added [" .
                    $productInStock .
                    "] units of the new product: [" .
                    $productName .
                    "] priced at [" .
                    $productPrice .
                    "]"
                );
            }

            break;
        case 2:
            $productChoice = (int)readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $withdrawAmount = (int)readline("Withdraw Amount: ");
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
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
                $user,
                "withdrew [" .
                $withdrawAmount .
                "] units from product: [" .
                $selectedProduct->getName() .
                "]"
            );
            break;
        case 3:
            $productChoice = (int)readline("Enter product index: ");
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
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
                $user,
                "changed the name of the product: [" .
                $previousProductName .
                "] to a new name: [" .
                $productName .
                "]"
            );
            break;
        case 4:
            $productChoice = (int)readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $productInStock = (int)readline("Units in stock: ");
            if ($productInStock <= 0) {
                readline(
                    "Invalid input. Amount of units in stock cant be less than 1. Press any key to continue..."
                );
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $previousUnitAmount = $selectedProduct->getUnits();
            $selectedProduct->setUnits($productInStock);
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
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
            $productChoice = (int)readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $productPrice = (float)readline("New product price: ");
            if ($productPrice <= 0) {
                readline(
                    "Invalid input. Product price cant be less than 0. Press any key to continue..."
                );
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $previousProductPrice = $selectedProduct->getUnits();
            $selectedProduct->setPrice($productPrice);
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
                $user,
                "changed product: [" .
                $selectedProduct->getName() .
                "] price from [" .
                $previousProductPrice .
                "] to [" .
                $productPrice .
                "]"
            );
            break;
        case 6:
            $productChoice = (int)readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            echo "Use format [YYYY-MM-DD]\n";
            $productExpirationDate = readline("Enter product quality expiration date: ");
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $previousProductExpirationDate = $selectedProduct->getExpirationDate();
            $selectedProduct->setExpirationDate($productExpirationDate);
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
                $user,
                "changed product: [" .
                $selectedProduct->getName() .
                "] quality expiration date from [" .
                $previousProductExpirationDate .
                "] to [" .
                $productExpirationDate .
                "]"
            );
            break;
        case 7:
            $productChoice = (int)readline("Enter product index: ");
            if (
                !isInputValid($productChoice, 1, count($warehouse->products()))
            ) {
                readline("Invalid input. Press any key to continue...");
                break;
            }
            $selectedProduct = $warehouse->products()[$productChoice - 1];
            $warehouse->createLog(
                $selectedProduct->getUpdatedAt(),
                $selectedProduct->getId(),
                $user,
                "removed product: [" .
                $selectedProduct->getName() .
                "]"
            );
            $warehouse->removeProduct($productChoice - 1);
            break;
        case 8:
            if ($warehouse) {
                echo $warehouse->createReport();
                $warehouse->saveReport();
                readline("Press any key to continue...");
            }
            break;
        case 9:
            $warehouse->viewLogs();
            readline("Press any key to continue...");
            break;
        case 10:
            return false;
        default:
            readline("Invalid input. Press any key to continue...");
    }
} while (true);
