<?php

namespace hachkingtohach1\Napthe;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\ConsoleCommandSender;
class checktheTask extends AsyncTask
{
	private $arrayPost;
	private $playerName;
	public function __construct($arrayPost,string $playerName){
		$this->arrayPost = $arrayPost;
		$this->playerName = $playerName;
	}
    /**
     *
     */
    public function onRun(): void
    {
            $api_url = "https://trumthe.vn//chargingws/v2";
				
			$arrayPost = $this->arrayPost;
			$arrayPost["command"] = "check";
            $curl = curl_init($api_url);
                curl_setopt_array($curl, array(
	            CURLOPT_POST => true,
	            CURLOPT_HEADER => false,
	            CURLINFO_HEADER_OUT => true,
	            CURLOPT_TIMEOUT => 120,
	            CURLOPT_RETURNTRANSFER => true,
	            CURLOPT_SSL_VERIFYPEER => false,
	            CURLOPT_POSTFIELDS => http_build_query($arrayPost)
            ));
            $data = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $result = json_decode($data, true);
			$content= [
				"web_status" => $status,
				"result" => $result
			];
			$this->setResult($content);			
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server): void
    {
        if (is_null($napthe = Napthe::$instance)) {
            return;
        }

        $content = $this->getResult();
		if (!isset($content)) {
            $napthe->getServer()->getLogger()->info("§6[§bNAPTHE§6]§f Cant get updated information (timed out?)");
            return;
        }
		if($content["web_status"] == 200){
			if($content["result"]["status"] == 1){
				$ucoin = $napthe->chuyendoi[(string) $content["result"]["value"]];
				$napthe->napThanhCong($this->playerName,(int) $content["result"]["value"],$ucoin);
				return;
			}
			$player = $napthe->getServer()->getPlayerExact($this->playerName);
			
			if($content["result"]["status"] == 99){
				$napthe->getServer()->getAsyncPool()->submitTask(new checktheTask($this->arrayPost,$this->playerName));
				if($player == null){		
					return;
				}	
				$player->sendTip("§l✾§aĐang kiểm tra thẻ, xin §cđừng chat§a lúc này vì bạn sẽ không nhận được bảng thông tin phản hồi...");
				return;
			}
			
			if($player == null){		
				return;
			}	
			if($content["result"]["status"] == 2){
					$txt = 
					"§l✾§cBạn đã chọn sai mệnh giá\n\n".
					"§f§l✾Thẻ giá trị: §a".$content["result"]["value"]."\n\n".
					"§f§l✾Giá trị bạn chọn: §a".$content["result"]["declared_value"]."\n\n".
					"§f§l§c•HÃY CHỤP MÀN HÌNH VÀ GỬI ADMIN ĐỂ ĐƯỢC HỖ TRỢ!\n\n";
					$napthe->getServer()->broadcastMessage("\n=================\n\n✾§eCảm ơn bạn §a".$this->playerName."§e đã\nnạp thẻ (§a".$content["result"]["value"]."§e) ủng hộ server\n§cTuy nhiên bạn đã chọn sai mệnh giá lúc nạp§r\n\n=================");
					$player->sendMessage($txt);				
					$napthe->onSuccess($player,$txt);
			}else{
					$txt = 
					"§l✾§cĐã có lỗi xảy ra\n\n".
					"§f§l✾Mã giao dịch: §c".$content["result"]["request_id"]."\n\n".
					"§f§l✾Thông tin lỗi:§c ".$content["result"]["message"]."\n\n".
					"§f§l✾Giải thích :§a Có thể bạn đã nhập sai seri,mã thẻ. Hãy kiểm tra lại\n\n".
					"§f§l•§aHãy chụp màn hình và báo admin nếu cần hỗ trợ!\n\n";
					$player->sendMessage($txt);			
					$napthe->onSuccess($player,$txt);
			}
		}else{
			
		}
    }
}
