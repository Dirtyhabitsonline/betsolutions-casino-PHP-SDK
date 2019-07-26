<?php


namespace Betsolutions\Casino\SDK\Slots\Campaigns\Services;


use Betsolutions\Casino\SDK\Exceptions\CantConnectToServerException;
use Betsolutions\Casino\SDK\MerchantAuthInfo;
use Betsolutions\Casino\SDK\Services\BaseService;
use Betsolutions\Casino\SDK\Slots\Campaigns\DTO\CreateSlotCampaignRequest;
use Betsolutions\Casino\SDK\Slots\Campaigns\DTO\CreateSlotCampaignResponseContainer;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use JsonMapper;
use JsonMapper_Exception;

class SlotCampaignService extends BaseService
{
    public function __construct(MerchantAuthInfo $authInfo)
    {
        parent::__construct($authInfo, 'SlotCampaign');
    }

    private function generateBetAmountsPerCurrency(array $betAmounts)
    {

        $result = array();

        foreach ($betAmounts as $k => $v) {

            $newObj['CoinCount'] = $v->coinCount;
            $newObj['CoinValueId'] = $v->coinValueId;
            $newObj['Currency'] = $v->currency;

            array_push($result, $newObj);
        }

        return $result;
    }

    /**
     * @param CreateSlotCampaignRequest $request
     * @return CreateSlotCampaignResponseContainer
     * @throws CantConnectToServerException
     * @throws JsonMapper_Exception
     */
    public function createSlotCampaign(CreateSlotCampaignRequest $request): CreateSlotCampaignResponseContainer
    {
        $url = "{$this->authInfo->baseUrl}/{$this->controller}/CreateSlotCampaign";

        $betAmounts = $this->generateBetAmountsPerCurrency($request->betAmountsPerCurrency);

        $betAmountsJson = json_encode($betAmounts);
        $playerIdsJson = json_encode($request->playerIds);

        $rawHash = "{$request->campaignTypeId}|{$request->endDate}|{$request->startDate}|{$request->freespinCount}|{$request->gameId}|{$this->authInfo->merchantId}|{$request->name}|{$betAmountsJson}|{$playerIdsJson}|{$this->authInfo->privateKey}";

        $hash = $this->getSha256($rawHash);

        $data['MerchantId'] = $this->authInfo->merchantId;
        $data['PlayerIds'] = $request->playerIds;
        $data['BetAmountsPerCurrency'] = $betAmounts;
        $data['StartDate'] = $request->startDate;
        $data['EndDate'] = $request->endDate;
        $data['GameId'] = $request->gameId;
        $data['Name'] = $request->name;
        $data['FreespinCount'] = $request->freespinCount;
        $data['AddNewlyRegisteredPlayers'] = $request->addNewlyRegisteredPlayers;
        $data['CampaignTypeId'] = $request->campaignTypeId;
        $data['Hash'] = $hash;

        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $response = Request::post($url, json_encode($data))
                ->expectsJson()
                ->sendsJson()
                ->send();

        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (ConnectionErrorException $e) {

            throw new CantConnectToServerException($e->getCode(), $e->getMessage());
        }

        $result = new CreateSlotCampaignResponseContainer();
        $mapper = new JsonMapper();

        $result = $mapper->map($response->body, $result);

        return $this->castCreateSlotCampaignModel($result);
    }

    private function castCreateSlotCampaignModel($obj): CreateSlotCampaignResponseContainer
    {
        return $obj;
    }
}