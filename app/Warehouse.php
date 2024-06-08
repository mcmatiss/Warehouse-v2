<?php

namespace App;

class Warehouse
{
    private array $products = [];
    private array $log = [];
    private const STORAGE_PATH = 'data/';

    public function __construct()
    {
        if (file_exists(self::STORAGE_PATH . "logs.json")) {
            $log = json_decode(file_get_contents("data/logs.json"));
            foreach ($log as $oldEntry) {
                $this->log[] = $oldEntry;
            }
        }

        if (file_exists(self::STORAGE_PATH . "storedProducts.json")) {
            $products = file_get_contents(self::STORAGE_PATH . "storedProducts.json");
            $products = json_decode($products);

            foreach ($products as $product) {
                $this->addProduct(new Product(
                    $product->name,
                    $product->units,
                    $product->price,
                    $product->expirationDate,
                    $product->id,
                    $product->createdAt,
                    $product->updatedAt
                ));
            }
        }
    }

    public function products(): array
    {
        return $this->products;
    }

    public function addProduct(Product $product): void
    {
        $this->products[] = $product;

    }

    public function removeProduct(int $index): void
    {
        array_splice($this->products, $index, 1);
    }

    public function createLog(string $time, string $id, string $user, string $msg): void
    {
        $this->log[] = "[$time] product id: [$id] user: [$user] - $msg";

        $this->updateLogs();

        $this->save();
    }

    public function viewLogs(): void
    {
        foreach ($this->log as $entry => $log) {
            echo "[$entry] $log\n";
        }
    }


    public function createReport(): string
    {
        return "\nTotal amount of products: " .
            $this->totalAmountOfProducts() .
            "\nTotal amount of units: " .
            $this->totalAmountOfUnits() .
            "\nTotal product value in EUR: " .
            $this->totalProductValue() .
            "\n";
    }

    public function saveReport(): void
    {
        file_put_contents(
            self::STORAGE_PATH .
            "report.json",
            json_encode($this->createReport(),
                JSON_PRETTY_PRINT
            )
        );
    }

    private function totalAmountOfProducts(): int
    {
        return count($this->products);
    }

    private function totalAmountOfUnits(): int
    {
        $sum = 0;
        foreach ($this->products as $product) {
            $sum += $product->getUnits();
        }
        return $sum;
    }

    private function totalProductValue(): float
    {
        $sum = 0;
        foreach ($this->products as $product) {
            $sum += $product->getPrice();
        }
        return $sum;
    }

    private function updateLogs(): void
    {
        file_put_contents(
            self::STORAGE_PATH .
            "logs.json",
            json_encode($this->log,
                JSON_PRETTY_PRINT
            )
        );
    }

    private function save(): void
    {
        file_put_contents(
            self::STORAGE_PATH .
            "storedProducts.json",
            json_encode($this->products,
                JSON_PRETTY_PRINT
            )
        );
    }
}
