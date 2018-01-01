<?php

include('/etc/inc/openvpn.inc');

class vpn_resync{

    private $ips            = array('208.167.254.51','104.200.153.75','64.237.52.141',);
    private $config_file    = '/var/etc/openvpn/client1.conf';
    private $ping_command   = 'ping -c 3 8.8.8.8';//4.2.2.2
    private $curr_ip        = '';
    private $curr_port      = '1198';
    private $new_ip         = '208.167.254.51';
    private $config         = FALSE;
    private $pattern        = '/(remote)/';

    public function update(){


        //$conn_state = FALSE;
        $conn_state = $this->ping();

        /**
         * Connected
         */
        if($conn_state === TRUE){
            echo 'Still Connected.'."\n";
        }

        /**
         * Not Connected
         */
        if($conn_state === FALSE){

            echo 'Not Connected...'."\n";

            if($this->load_config() !== FALSE){
                echo 'Config Loaded...'."\n";

                if($this->update_config() !== FALSE){
                    echo 'Config Updated...'."\n";
                    $this->reload_vpn();

                }else{
                    echo 'Unable To Update Config...'."\n";
                }

            }else{
                echo 'Failed To Load Config...'."\n";
            }

        }


        /**
         *  Unable To Check
         */
        if($conn_state === 2){
            echo 'Failed to check connection!'."\n";
        }

        echo 'Finished...'."\n";

    }

    private function reload_vpn(){
       openvpn_resync_all();
      //shell_exec($cmd);
    }

    private function ping(){

      $response = false;

        $shell = shell_exec($this->ping_command);

        if($shell !== null){

            $pos = strpos($shell, '100%');

            if($pos === TRUE){
              $response = FALSE;
            }else{
              $response = TRUE;
            }

        }else{

            echo 'Ping command failed!'."\n";
            $response = 2;
        }

      return $response;

    }

    private function update_config(){
        $this->write_file();
    }

    private function load_config(){

      $response   = FALSE;

      $line_number = '';

      $config = $this->get_config();

      if(is_array($config)){

        /**
         *  Find the Line we need
         */
        foreach ($config as $key => $value) {
            if(preg_match($this->pattern, $value)){
                $line_number = $key;
                echo 'Found Config Line...'."\n";
            }
        }

        // Split line into array
        $line = explode(' ', trim($config[$line_number]));

        /**
         *  Make sure we have the right line
         */
        if(isset($line[0]) && isset($line[1]) && ctype_alpha($line[0]) && strtolower($line[0]) == 'remote'){

            echo 'Have Current ip...'."\n";

            $this->curr_ip = $line[1];
        }

        /**
         * Check if we have a current ip
         */
        if($this->curr_ip !== ''){

          if(is_array($this->ips)){

            // Keep it mixed up.
            shuffle($this->ips);

            // Make sure we dont use the same ip
            foreach($this->ips as $k => $v){
              if($v !== $this->curr_ip){
                  $this->new_ip = $v;
              }
            }

          }else{
            echo '$this->ips is not an Array...'."\n";
          }

        }else{

            // Set new ip to the first in the list.
            $this->new_ip = $this->ips[0];

        }

        if(isset($line[2])){
            $this->curr_port = $line[2];
        }

        echo 'New ip set to: '.$this->new_ip."...\n";

        $config[$line_number] = 'remote '.$this->new_ip.' '.$this->curr_port."\n";
        $this->config = $config;

        if($this->config !== FALSE && is_array($this->config)){
          $response = TRUE;
          echo 'Config Data Set...'."\n";
        }

      }

      return $response;

    }


    private function get_config(){

        //$f = '/home/jphreak/scripts/network/testconfig.conf';
        $response = FALSE;

        if($this->config_file !== '' && file_exists($this->config_file)){

          $file = @file($this->config_file);
          if(is_array($file) && count($file) > 0){

              echo 'Have Config File...'."\n";
              $response = $file;

          }else{

              echo 'Config File Was Empty...'."\n";
              echo 'Using Default Config...'."\n";
              $response = $this->__config();
              echo 'Have Config...'."\n";
          }

        }else{

            echo 'Confg File Not Found...'."\n";
            echo 'Please Check File Location or File Exist...'."\n";
            echo 'Config File: '.$this->config_file."\n";
        }

        return $response;

    }

    private function write_file(){

        $response = FALSE;

        $file_handle = FALSE;

        if(file_exists($this->config_file)){

            $file_handle = @fopen($this->config_file, 'w');
            if($file_handle !== FALSE){
                foreach ($this->config as $key => $value) {
                    //$value.="\n";
                    $d = fwrite($file_handle, $value);

                }
            }else{
                echo 'Couldn\'t Load File...'."\n";
                echo 'Unable to Write New Config...'."\n";
            }
            fclose($file_handle);
        }

        return $response;

    }

    private function __config($value=''){
        echo 'Building Config...'."\n";
        $response = array(
            '0'=>'dev ovpnc1',
            '1'=>'verb 3',
            '2'=>'dev-type tun',
            '3'=>'dev-node /dev/tun1',
            '4'=>'writepid /var/run/openvpn_client1.pid',
            '5'=>'#user nobody',
            '6'=>'#group nobody',
            '7'=>'script-security 3',
            '8'=>'daemon',
            '9'=>'keepalive 10 60',
            '10'=>'ping-timer-rem',
            '11'=>'persist-tun',
            '12'=>'persist-key',
            '13'=>'proto udp',
            '14'=>'cipher AES-256-CBC',
            '15'=>'auth SHA1',
            '16'=>'up /usr/local/sbin/ovpn-linkup',
            '17'=>'down /usr/local/sbin/ovpn-linkdown',
            '18'=>'local 192.168.0.2',
            '19'=>'tls-client',
            '20'=>'client',
            '21'=>'lport 0',
            '22'=>'management /var/etc/openvpn/client1.sock unix',
            '23'=>'remote 123.123.123.123 1198',
            '24'=>'auth-user-pass /var/etc/openvpn/client1.up',
            '25'=>'ca /var/etc/openvpn/client1.ca',
            '26'=>'comp-lzo adaptive',
            '27'=>'resolv-retry infinite',
            '28'=>'persist-key',
            '29'=>'persist-tun',
            '30'=>'reneg-sec 0',
            '31'=>'verb 5',
            '32'=>'',
            '33'=>'auth-nocache',
            '34'=>'',
            '35'=>'keepalive 10 30',
            '36'=>'tun-mtu 1500',
            '37'=>'--script-security 2',
            '38'=>'',
        );
        echo 'Config Built...'."\n";
        return $response;
    }

    private function __log($log_data){

    }

}
$vpn = new vpn_resync;
$vpn->update();
?>
