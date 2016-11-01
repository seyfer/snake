<?php

namespace PHPSnake;

/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 11/1/16
 */
class SnakeGame
{
    /** @var array */
    private $snakes = [];

    /**
     * Key mappings
     * @var array
     */
    private $mappings = [
        [
            65 => 'up',
            66 => 'down',
            68 => 'left',
            67 => 'right',
            56 => 'up',
            50 => 'down',
            52 => 'left',
            54 => 'right',
        ],
        [
            119 => 'up',
            115 => 'down',
            97  => 'left',
            100 => 'right',
        ],
    ];

    public function __construct()
    {
    }

    /**
     * Adds a snake to the game
     * @param Snake $s
     * @return SnakeGame
     */
    public function addSnake(Snake $s) : SnakeGame
    {
        $this->snakes[] = $s;

        return $this;
    }

    /**
     * Runs the game
     */
    public function run() : void
    {
        if (count($this->snakes) < 1) {
            throw new \Exception('Too few players!');
        }

        $mappings = [];
        foreach ($this->snakes as $i => $snake) {
            foreach ($this->mappings[$i] as $key => $dir) {
                $mappings[$key] = [$dir, $i];
            }
        }

        system('stty cbreak -echo');

        $stdin = fopen('php://stdin', 'r');

        while (1) {
            $c = ord(fgetc($stdin));
            echo "Char read: $c\n";

            if (isset($mappings[$c])) {
                $mapping = $mappings[$c];
                $this->snakes[$mapping[1]]->setDirection($mapping[0]);
            }

        }
    }
}