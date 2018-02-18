<?php

namespace Hichxm\WarGaming;

use Exception;
use GuzzleHttp\Client;


/**
 * Class wargaming
 * @package WargamingApi
 */
class WargamingApi
{
    private $key;
    private $region;

    private $links = [
        "accountSearch" => "api.worldoftanks.{region}/wgn/account/list/?application_id={key}&search={search}&limit={limit}&type={method}",
        "accountId" => "api.worldoftanks.{region}/wgn/account/info/?application_id={key}&account_id={accounts}",

        "serverInfo" => "api.worldoftanks.{region}/wgn/servers/info/?application_id={key}"
    ];

    /**
     * WargamingApi constructor.
     * @param string $key
     * @param string $region
     */
    public function __construct($key, $region)
    {
        $this->setKey($key);
        $this->setRegion($region);
    }

    /**
     * @param string $search
     * @param array|null $options
     * @return mixed
     * @throws Exception
     */
    public function searchPlayers($search, $options = null)
    {

        if (strlen($search) == 0) {

            //Search not specified
            throw new Exception("SEARCH_NOT_SPECIFIED", "402");
        } else if (strlen($search) <= 3) {

            //Search no enough
            throw new Exception("NOT_ENOUGH_SEARCH_LENGTH", "407");
        } else if (strlen($search) >= 100) {

            //Search as exceeded
            throw new Exception("SEARCH_LIST_LIMIT_EXCEEDED", "407");
        }

        $returned = $this->request("accountSearch", [
            "search" => $search,
            "limit" => !empty($options['limit']) ? $options['limit'] : 100,
            "method" => !empty($options['method']) ? $options['method'] : "startswith"
        ]);

        return [
            "count" => $returned['meta']['count'],
            "players" => $returned['data']
        ];

    }

    /**
     * @param array $accounts_id
     * @return mixed
     * @throws Exception
     */
    public function searchPlayer($accounts_id = [])
    {
        $accounts = null;
        foreach ($accounts_id as $account_id) {
            $accounts .= $account_id . ",";
        }

        $returned = $this->request("accountId", [
            "accounts" => $accounts
        ]);

        return [
            "count" => $returned['meta']['count'],
            "players" => $returned['data']
        ];

    }

    /**
     * @param string|null $region
     * @return array
     * @throws Exception
     */
    public function serverInfo($region = null)
    {
        $region = !empty($region) ? $region : $this->region;

        $returned = $this->request("serverInfo", [
            "region" => $region
        ])['data'];

        return [
            "wotb" => $returned['wotb'],
            "wot" => $returned['wot'],
            "wows" => $returned['wows']
        ];

    }

    /**
     * @param string $ref
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    private function request($ref, $options)
    {
        $link = $this->links[$ref];

        switch ($ref) {
            case "accountSearch":

                //Replace data of the link
                $link = str_replace("{search}", $options['search'], $link);
                $link = str_replace("{limit}", $options['limit'], $link);
                $link = str_replace("{method}", $options['method'], $link);
                break;

            case "accountId":

                //Replace data of the link
                $link = str_replace("{accounts}", $options['accounts'], $link);
                break;

            case "serverInfo":

                //Replace data of the link
                $link = str_replace("{region}", $options['region'], $link);
                break;
        }

        //Replace data of the link
        $link = str_replace("{region}", $this->region, $link);
        $link = str_replace("{key}", $this->key, $link);

        $client = new Client();
        $res = $client->request("GET", $link);
        $res = json_decode($res->getBody(), true);

        if ($res['status'] === "error") {
            throw new Exception("INVALID_APPLICATION_ID", 407);
        }

        return $res;

    }

    /**
     * @param string $region
     */
    private function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @param string $key
     */
    private function setKey($key)
    {
        $this->key = $key;
    }

}