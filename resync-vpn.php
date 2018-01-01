<?php

include('openvpn.inc');

class vpn_resync{

    private $ips            = array('208.167.254.51','104.200.153.75','64.237.52.141',);
    private $config_file    = '/home/user1123/testconf.conf';//'/var/etc/openvpn/client1.conf';
    private $ping_command   = 'ping -c 3 8.8.8.8';
    private $curr_ip        = '';
    private $curr_port      = '';
    private $new_ip         = '';

    public function update(){

        //$conn_state = FALSE;
        $conn_state = $this->ping();

        if($conn_state === FALSE){

            echo 'Not Connected...'."\n";

            // Get curr ip
            $curr_ip = $this->get_curr_ip();

            if($curr_ip === TRUE){

                echo 'Have current ip...'."\n";

                // Set the new ip
                $new_ip = $this->set_new_ip();

                if($new_ip === TRUE){

                    echo 'Have new ip...'."\n";
                    // Update file with new ip
                    $updated = $this->update_ip();

                    // Check the status
                    if($updated === FALSE){
                        echo 'Wasn\'t able to update ip in: '.$this->config_file."\n";
                    }else{
                        echo 'Updated ip!'."\n";
                        $this->reload_vpn();
                    }
                }else{

                    echo 'Wasn\'t able to get new ip...'."\n";
                }

            }else{

                echo 'Unable to get current ip.'."\n";

            }
        }

        if($conn_state === TRUE){
            echo 'Still Connected.'."\n";
        }

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

            $pos = strpos($shell, '0%');

            if($pos === FALSE){
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

    private function update_ip(){

        $response   = FALSE;

        $cmd        = "sed -i .bak '/remote/ c\'$''\n'remote $this->new_ip $this->curr_port' $this->config_file";
        $output     = '';
        $return_var = '';

        exec($cmd, $output, $return_var);

        if($return_var === 0){
            $response = TRUE;
        }

        if($return_var !== 0){
            $response = FALSE;
        }

        return $response;

    }

    private function set_new_ip(){

        $response = FALSE;

        // Make sure we dont use the same ip
        if($this->curr_ip !== ''){
           foreach($this->ips as $k => $v){
              if($v !== $this->curr_ip){
                $this->new_ip = $v;
                $response = TRUE;
              }
           }
        }

        return $response;
    }

    private function get_curr_ip(){

        $response   = FALSE;

        $cmd        = "sed '24q;d' $this->config_file";
        $output     = '';
        $return_var = '';

        exec($cmd, $output, $return_var);

        if($return_var === 0){


            if(isset($output[0])){

                $line_data = explode(' ', trim($output[0]));

                $this->curr_ip   = isset($line_data[1]) ? $line_data[1]:FALSE;
                $this->curr_port = isset($line_data[2]) ? $line_data[2]:FALSE;

                $response = TRUE;

            }else{

                $response = FALSE;

            }

        }

        if($return_var !== 0){
            $response = FALSE;
        }

        return $response;

    }

    private function __log($log_data){

    }

}

$vpn = new vpn_resync;
$vpn->update();

?>
