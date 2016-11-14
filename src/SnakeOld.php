<?php
/**
 * Created by PhpStorm.
 * User: seyfer
 * Date: 11/1/16
 */

namespace PHPSnake;

use React;

/**
 * Class SnakeOld
 * @package PHPSnake
 *
 * Should be refactored to Snake and SnakeGame classes
 */
class SnakeOld
{
    const UP    = 'up';
    const DOWN  = 'down';
    const LEFT  = 'left';
    const RIGHT = 'right';

    //red
    const COLOUR_TEXT = "\033[31m";
    //green
    const COLOUR_FOOD = "\033[32m";
    //blue
    const COLOUR_SNAKE_1 = "\033[33m";
    //yellow
    const COLOUR_SNAKE_2 = "\033[34m";

    //game duration
    const DURATION = 100;

    /**
     * @var boolean
     */
    private $running = false;

    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var int
     */
    private $playersCount = 0;

    /**
     * @var bool
     */
    private $multiplayer = false;

    /**
     * @var bool
     * it's a setting to remove dying from walls
     * and add timer for game end
     */
    private $keepAlive = false;

    /**
     * @var int
     */
    private $secondsRemaining = 0;

    /**
     * @var array
     */
    private $snakes = [];

    /**
     * @var array
     */
    private $directions = [];

    /**
     * @var array
     */
    private $buffer = [];

    /**
     * @var array
     */
    private $food = [];

    /**
     * @var null|string
     */
    private $loser = null;

    /**
     * SnakeOld constructor.
     * @param int $playersCount
     * @param bool $keepAlive
     */
    public function __construct(int $playersCount = 1, bool $keepAlive = false)
    {
        //next two commands return terminal сols and lines
        $this->width  = (int)exec('tput cols');
        $this->height = (int)exec('tput lines') - 1;

        $this->playersCount = $playersCount;
        $this->keepAlive    = $keepAlive;

        $this->multiplayer = $this->playersCount > 1;
    }

    /**
     * @return boolean
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * @return boolean
     */
    public function isMultiplayer(): bool
    {
        return $this->multiplayer;
    }

    public function run()
    {
        /*
         * disable canonical mode and -echo disables the output of input characters.
         */
        system('stty -icanon -echo');

        while (1) {
            $this->game();

            system('clear');

            echo self::COLOUR_TEXT;

            if ($this->isMultiplayer()) {
                if ($this->loser !== NULL) {
                    echo 'Player ' . ($this->loser + 1) . ' crashed!' . PHP_EOL . PHP_EOL;
                }

                echo 'Scores:' . PHP_EOL;

                foreach ($this->snakes as $key => $snake) {
                    echo "\t" . 'Player ' . ($key + 1) . ': ' . count($snake);
                }
            } else {
                echo 'You crashed!' . PHP_EOL . PHP_EOL;
                echo 'Score: ' . count($this->snakes[0]) . PHP_EOL;
            }

            sleep(4);
        }
    }

    public function game()
    {
        $this->init();

        $loop = React\EventLoop\Factory::create();

        $stdin = fopen('php://stdin', 'r');
        //non-blocking mode
        stream_set_blocking($stdin, 0);

        while (fgetc($stdin)) ;

        $loop->addReadStream($stdin, function ($stdin) {
            $key = ord(fgetc($stdin));

            $escapeSymbol = 27;
            if ($escapeSymbol === $key) {
                fgetc($stdin);
                $key = ord(fgetc($stdin));
            }

            switch ($key) {
                case 65:
                case ord('8'):
                    $this->setDirection(SnakeOld::UP);
                    break;
                case 66:
                case ord('2'):
                    $this->setDirection(SnakeOld::DOWN);
                    break;
                case 68:
                case ord('4'):
                    $this->setDirection(SnakeOld::LEFT);
                    break;
                case 67:
                case ord('6'):
                    $this->setDirection(SnakeOld::RIGHT);
                    break;
                case ord('w'):
                    $this->setDirection(SnakeOld::UP, 1);
                    break;
                case ord('s'):
                    $this->setDirection(SnakeOld::DOWN, 1);
                    break;
                case ord('a'):
                    $this->setDirection(SnakeOld::LEFT, 1);
                    break;
                case ord('d'):
                    $this->setDirection(SnakeOld::RIGHT, 1);
                    break;
                case 0:
                case ord(''):
                    exit(0);
                    break;
            }
        });

        $loop->addPeriodicTimer(0.1, function () use ($loop) {
            $active = $this->step();
            $this->render();
            $this->printGame();

            if (!$active) {
                $loop->stop();
            }
        });

        if ($this->keepAlive && $this->playersCount > 1) {
            $loop->addPeriodicTimer(1, function () use ($loop) {
                if ($this->isRunning()) {
                    $this->secondsRemaining--;

                    if ($this->secondsRemaining < 0) {
                        $loop->stop();
                    }
                }
            });
        }

        $loop->run();
    }

    public function init()
    {
        $this->loser   = NULL;
        $this->running = false;

        $this->secondsRemaining = self::DURATION;

        $this->directions = $this->snakes = [];

        for ($i = 0; $i < $this->playersCount; $i++) {
            $this->directions[] = NULL;

            $this->snakes[] = [[
                                   (int)(($i + 1) * ($this->width / ($this->playersCount + 1))),
                                   (int)($this->height / 2),
                               ]];
        }

        $this->newFood();
    }

    /**
     * @param string $direction
     * @param int $snake
     */
    public function setDirection(string $direction, int $snake = 0)
    {
        $this->running = true;

        if (count($this->snakes[$snake]) > 1) {
            switch ($this->directions[$snake]) {
                case self::UP:
                    if ($direction == self::DOWN) return;
                    break;
                case self::DOWN:
                    if ($direction == self::UP) return;
                    break;
                case self::LEFT:
                    if ($direction == self::RIGHT) return;
                    break;
                case self::RIGHT;
                    if ($direction == self::LEFT) return;
                    break;
            }
        }

        $this->directions[$snake] = $direction;
    }

    public function newFood()
    {
        do {
            $this->food = [
                mt_rand(0, $this->width - 1),
                mt_rand(0, $this->height - 1),
            ];
        } while (!$this->noFoodCollision());
    }

    /**
     * @return bool
     */
    public function noFoodCollision()
    {
        foreach ($this->snakes as $snake) {
            foreach ($snake as $point) {
                if ($this->pointIsSame($point, $this->food)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array $x
     * @param array $y
     * @return bool
     */
    public function pointIsSame(array $x, array $y)
    {
        return $x[0] === $y[0] && $x[1] === $y[1];
    }

    /**
     * @return bool
     */
    public function step()
    {
        foreach ($this->snakes as $key => $snake) {
            if (!$this->snakeStep($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $key
     * @return bool
     */
    public function snakeStep(int $key)
    {
        if ($this->directions[$key] === NULL) {
            return true;
        }

        $newPoint = $this->snakes[$key][count($this->snakes[$key]) - 1];

        switch ($this->directions[$key]) {
            case self::UP:
                $newPoint[1]--;
                break;
            case self::DOWN:
                $newPoint[1]++;
                break;
            case self::LEFT:
                $newPoint[0]--;
                break;
            case self::RIGHT:
                $newPoint[0]++;
                break;
        }

        foreach ($this->snakes as $otherSnake => $snake) {
            foreach ($snake as $index => $point) {
                $facePoint = ($index == count($snake) - 1);

                if ($this->pointIsSame($point, $newPoint)) {
                    if ($this->keepAlive) {
                        if ($facePoint) {
                            $this->snakes[$otherSnake] = [$point];
                        }

                        $this->snakes[$key] = [$this->snakes[$key][0]];
                        break;
                    } else {
                        $this->loser = $key;

                        return false;
                    }
                }
            }
        }

        $bounce = false;

        if (
            $newPoint[0] < 0 || $newPoint[0] >= $this->width ||
            $newPoint[1] < 0 || $newPoint[1] >= $this->height
        ) {
            if ($this->keepAlive) {
                if ($newPoint[0] < 0) $newPoint[0] = $this->width - 1;
                if ($newPoint[1] < 0) $newPoint[1] = $this->height - 1;
                if ($newPoint[0] >= $this->width) $newPoint[0] = 0;
                if ($newPoint[1] >= $this->height) $newPoint[1] = 0;
            } else {
                $this->loser = $key;

                return false;
            }
        }

        $this->snakes[$key][] = $newPoint;

        if ($this->pointIsSame($newPoint, $this->food)) {
            $this->newFood();
        } else {
            array_shift($this->snakes[$key]);
        }

        return true;
    }

    public function render()
    {
        $this->renderBackground();
        $this->renderSnake();
        $this->renderFood();
    }

    public function renderBackground()
    {
        $this->buffer = [];

        for ($j = 0; $j < $this->height; $j++) {
            $this->buffer[$j] = [];

            for ($i = 0; $i < $this->width; $i++) {
                $this->buffer[$j][$i] = ' ';
            }
        }
    }

    public function renderSnake()
    {
        foreach ($this->snakes as $player => $snake) {
            $colour = $player ? self::COLOUR_SNAKE_1 : self::COLOUR_SNAKE_2;

            foreach ($snake as $key => $point) {
                if ($key === count($snake) - 1) {
                    $char = $colour . "\xF0\x9F\x98\x88";
                } /* elseif ($key == 0) { // TAIL
          $char = "\xE2\x9D\x9A";
        } */ else {
                    $char = $colour . "\xE2\x96\x88";
                }

                $this->buffer[$point[1]][$point[0]] = $char;
            }
        }
    }

    public function renderFood()
    {
        $this->buffer[$this->food[1]][$this->food[0]] = self::COLOUR_FOOD . "\xF0\x9F\x8D\xB2";
    }

    public function printGame()
    {
        echo "\033[J";

        for ($y = 0; $y < $this->height; $y++) {
            $line = implode('', $this->buffer[$y]);
            echo PHP_EOL . $line;
        }

        echo self::COLOUR_TEXT;

        foreach ($this->snakes as $key => $snake) {
            echo 'Player ' . ($key + 1) . ': ' . count($snake) . "\t";
        }

        if ($this->secondsRemaining < self::DURATION) {
            echo "\t" . $this->secondsRemaining;
        }

        echo "\e[?25l";
    }
}