<?php
/**
 * Created by PhpStorm.
 * User: joeldg
 * Date: 6/25/17
 * Time: 12:57 PM.
 */

namespace Bowhead\Console\Commands;

use Bowhead\Util\Coinbase;
use Bowhead\Util\Console;
use Illuminate\Console\Command;

/**
 * Class WebsocketCommand.
 */
class WebsocketCoinbaseTestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bowhead:wscoinbase_test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the GDAX websocket and display realitime data in the terminal';

    /**
     * @var currency pairs
     */
    protected $instrument;

    /**
     * @var
     */
    protected $console;

    /**
     * @var current book
     */
    public $book;

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $this->console = $util = new Console();

        $this->instrument = 'BTC-USD';
        $loop = \React\EventLoop\Factory::create();
        $connector = new \Ratchet\Client\Connector($loop);

        $connector('wss://ws-feed.gdax.com')
            ->then(function (\Ratchet\Client\WebSocket $conn) {
                $conn->send('{"type": "subscribe","product_id": "'.$this->instrument.'"}');
                $conn->on('message', function (\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
                    /*
                     *   DO ALL PROCESSING HERE
                     *   match up sequence and keep the book up to date.
                     */
                    if (empty($this->book)) {
                        $this->book = $this->getBook($this->instrument);
                        $this->processBook();
                        $this->displayBook();
                    }
                    $this->displayPage(json_decode($msg, 1));
                    //echo "Received: {$msg}\n";
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    /* log errors here */
                    echo "Connection closed ({$code} - {$reason})\n";
                });
            }, function (\Exception $e) use ($loop) {
                /* hard error */
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();
    }

    public function displayPage($message)
    {
        if ('match' == $message['type']) {
            print_r($message);
        }

        $cols = getenv('COLUMNS');
        $rows = getenv('LINES');

        //print_r($message);
    }

    /**
     * @return mixed
     */
    private function getBook($instrument)
    {
        $util = new Coinbase();

        return $util->get_endpoint('book', null, '?level=2', $instrument);
    }

    /**
     *  reformat $this->book.
     */
    private function processBook()
    {
        $_bids = array_reverse($this->book['bids']);
        $_asks = array_reverse($this->book['asks']);

        foreach ($_bids as $bid) {
            $bids[$bid[0]] = ($bid[1] * $bid[2]); //array($bid[1], $bid[2], 0);
        }
        foreach ($_asks as $ask) {
            $asks[$ask[0]] = ($ask[1] * $ask[2]); //array($ask[1], $ask[2], 0);
        }
        $this->book = array('sell' => $asks, 'buy' => $bids);

        print_r($this->book);
        die();
    }

    public function displayBook($modify = null)
    {
        $cols = getenv('COLUMNS');
        $rows = getenv('LINES');
        $halfway = round(($rows / 2) - 1);

        if (!empty($modify)) {
            if ('received' == $modify['type'] || 'match' == $modify['type']) {
                return true;
            }

            if ('open' == $modify['type']) {
                if ('sell' == $modify['side']) {
                    $this->book['sell'][$modify['price']] = array(@$modify['remaining_size'], 1, 1);
                } else {
                    $this->book['buy'][$modify['price']] = array(@$modify['remaining_size'], 1, 1);
                }
            } elseif ('done' == $modify['type']) {
                if ('sell' == $modify['side']) {
                    unset($this->book['sell'][@$modify['price']]);
                } else {
                    unset($this->book['buy'][@$modify['price']]);
                }
            }
        }

        foreach ($this->book['sell'] as $key => $sell) {
            $line = str_pad(money_format('%.2n', $key), 10, ' ', STR_PAD_LEFT).str_pad($sell[0], 15, ' ', STR_PAD_LEFT);
            $color = (1 == $sell[2] ? 'bg_light_red' : 'light_red');
            $lines[$key] = $this->console->colorize($line, $color)."\n";
            $this->book['sell'][$key][2] = 0;
        }
        krsort($lines);
        $sells = array_slice($lines, -$halfway);
        foreach ($sells as $sell) {
            echo $sell;
        }
        echo "----------|-----------------\n";
        $lines = array();
        foreach ($this->book['buy'] as $key => $buy) {
            $line = str_pad(money_format('%.2n', $key), 10, ' ', STR_PAD_LEFT).str_pad($buy[0], 15, ' ', STR_PAD_LEFT);
            $bcolor = (1 == $buy[2] ? 'bg_light_green' : 'light_green');
            $lines[$key] = $this->console->colorize($line, $bcolor)."\n";
            $this->book['buy'][$key][2] = 0;
        }
        ksort($lines);
        $buys = array_slice($lines, -$halfway);
        foreach ($buys as $buy) {
            echo $buy;
        }

        return true;
    }
}
