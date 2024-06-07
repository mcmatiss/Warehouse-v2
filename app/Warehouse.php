<?php
namespace App;

use Carbon\Carbon;

class Warehouse implements \JsonSerializable
{
    private array $products = [];
    private array $log = [];
    private const STORAGE_PATH = 'data/';
    public function __construct()
    {
        $products = file_get_contents(self::STORAGE_PATH . "storedProducts.json");
        $products = json_decode($products);

        foreach ($products as $product)
        {
            $this->addProduct(new Product(
                $product->id,
                $product->name,
                $product->createdAt,
                $product->updatedAt,
                $product->units
            ));
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
    }
    public function updateLogs(array $logs): void
    {
        foreach ($logs as $oldLog) {
            $this->log[] = $oldLog;
        }
    }
    public function getLogs(): array
    {
        return $this->log;
    }

    public function viewLogs(): void
    {
        foreach ($this->log as $entry => $log)
        {
            echo "[$entry] $log\n";
        }
    }
    public function jsonSerialize()
    {
        return json_encode($this->products, JSON_PRETTY_PRINT);
    }
}