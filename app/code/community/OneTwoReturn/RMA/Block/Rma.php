<?php


class OneTwoReturn_RMA_Block_Rma extends Mage_Core_Block_Template
{
    private $logShown       = false;

    
    public function redirectTo($action)
    {
        die(header("location: ".Mage::getUrl("12return/form/".$action)));
    }
    
    

    public function showSubTitle($page)
    {
        $orderSession = $this->getSession('order');
        if(isset($orderSession['orderId']) && !empty($orderSession['orderId'])&& isset($orderSession['dateCreated']) && !empty($orderSession['dateCreated']))
        {
            $Config     = $this->getSession('Config');
            
            
            if($Config['loginType']=='user')
            {
                if($page=='selectorder')$htmlmeta='<div class="subTitle "></div>';else $htmlmeta= '<div class="subTitle"><strong>'.$this->translate('order').':</strong> '.$orderSession['orderId'].' &nbsp;&nbsp;&nbsp; <strong>'.$this->translate('datum').':</strong> '.$orderSession['dateCreated'].' </div>';
                
                $html='<div class="breadCrumb_rma breadcrumbs"><ul>';
                $html.='<li class="home"><a href="../../../" title="Home">Home</a><span class="sep">/</span></li>';
                if(isset($Config['currentType']))$html .='<li><a href="../../" title="'.$Config['labelname'].'">'.$Config['labelname'].'</a><span class="sep">/</span></li>';
            
                if($page=='selectorder')return $html."<li><i>".$this->translate('orderSelectie').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
                $html.='<li><a href="/12return/form/selectorder/">'.$this->translate('orderSelectie').'</a></li>';
                if($page=='selectproduct')return $html.'<li><span class="sep">/</span><i>'.$this->translate('orderProduct').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
                
                $html.='<li><span class="sep">/</span><a href="/12return/form/selectproduct/">'.$this->translate('orderProduct').'</a></li>';
                if($page=='retourinformatie')return $html.'<li><span class="sep">/</span><i>'.$this->translate('RetourInformatie').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            } else {
                if($page=='selectorder')$htmlmeta='<div class="subTitle "></div>';else $htmlmeta= '<div class="subTitle"><strong>'.$this->translate('order').':</strong> '.$orderSession['orderId'].' &nbsp;&nbsp;&nbsp; <strong>'.$this->translate('datum').':</strong> '.$orderSession['dateCreated'].' </div>';
                $html='<div class="breadCrumb_rma breadcrumbs"><ul>';
                $html.='<li class="home"><a href="../../../" title="Home">Home</a><span class="sep">/</span></li>';
                if(isset($Config['currentType']))$html .='<li><a href="../../" title="'.$Config['labelname'].'">'.$Config['labelname'].'</a><span class="sep">/</span></li>';
            
                if($page=='selectorder')return $html."<li><i>".$this->translate('orderSelectie').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
                $html.='<li><a href="/12return/form/selectorder/">'.$this->translate('orderSelectie').'</a> <span class="sep">/</span></li>';
                
                if($page=='selectproduct')return $html.'<li><i>'.$this->translate('orderProduct').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
                
                $html.='<li><a href="/12return/form/selectproduct/">'.$this->translate('orderProduct').'</a></li>';
                if($page=='retourinformatie')return $html.'<li><span class="sep">/</span> <i>'.$this->translate('RetourInformatie').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            }
            
            
            if($page=='externalprocess')return $html.'<li><span class="sep">/</span><i>'.$this->translate('Redirectprocess').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            
            $html.='<li><span class="sep">/</span><a href="/12return/form/retourinformatie/">'.$this->translate('RetourInformatie').'</a></li>';
            if($page=='klantgegevens')return $html.'<li><span class="sep">/</span><i>'.$this->translate('KlantGegevens').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            
            $html.='<li><span class="sep">/</span><a href="/12return/form/klantgegevens/">'.$this->translate('KlantGegevens').'</a></li>';
            
            
            $sessionXML     = $this->getSession('xml');
            if(isset($sessionXML['reutilization']['reutilcosts']['reutilcost']) && isset($sessionXML['reutilization']['reutilcosts']['reutilcost'][0]['reutilamount'])&&$sessionXML['reutilization']['reutilcosts']['reutilcost'][0]['reutilamount']!=0 && $sessionXML['reutilization']['reutilcosts']['reutilcost'][0]['reutilamounttype'] !='N' && !isset($sessionXML['reutilization']['reutilcosts']['reutilcost'][1]))
            {
                if($page=='servicekosten')return $html.'<li><span class="sep">/</span><i>'.$this->translate('ServiceKosten').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
                $html.='<li><span class="sep">/</span><a href="/12return/form/servicekosten/">'.$this->translate('ServiceKosten').'</a></li>';
            }
            if($page=='supplychain')return $html.'<li><span class="sep">/</span><i>'.$this->translate('supplychain').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            $html.='<li><span class="sep">/</span><a href="/12return/form/supplychain/">'.$this->translate('supplychain').'</a></li>';
            if($page=='checkout')return $html.'<li><span class="sep">/</span><i>'.$this->translate('Checkout').'</i></li></ul></div>'.$htmlmeta.'<br class="clearfix"/>';
            
            return $html.$htmlmeta.'<br />';
        } else {
            return'';
        }
    }
    
    public function translate($key) //Wordt gebruikt voor de vertalingen in de blokken zel.
    {
        //if(isset($this->translations[$key])) return $this->__($this->translations[$key]); else return 'MISSING-'.$key;
        return $this->__($key); 
    }
    

    public function resetSession($key='')               //Functie om data tijdelijk in een sessie op te slaan.
    {
        $key = strtolower($key);
        if(empty($key))Mage::getSingleton('core/session')->setReturnModuleSession(''); else {
            $session = Mage::getSingleton('core/session')->getReturnModuleSession();
            if(isset($session[$key])){$session[$key]=''; unset($session[$key]);}
            Mage::getSingleton('core/session')->setReturnModuleSession('');
            Mage::getSingleton('core/session')->setReturnModuleSession($session);
        }
        return true;
    }

    public function getSession($key)                //Functie om data uit een sessie te halen
    {
        $key = strtolower($key);
        $session = Mage::getSingleton('core/session')->getReturnModuleSession();
        if(isset($session[$key]) && !empty($session[$key]))return $session[$key];else return false;
    }
    
    public function addToSession($key,$data)        //Functie om data aan een bestande sessie toe te voegen of aan te passen.
    {
        $key = strtolower($key);
        $session = Mage::getSingleton('core/session')->getReturnModuleSession();
        if(!isset($session[$key]))$session[$key]=$data; else {
            if(is_array($session[$key]) && is_array($data)) $session[$key] = array_merge($session[$key],$data); else $session[$key]=$data;
        }
        Mage::getSingleton('core/session')->setReturnModuleSession($session);
        return $this->sessionStarted=true;
    }
    
    public function showLogs()
    {
        $config = $this->getSession('Config');
        if($config['debug']=='true' && !$this->logShown)
        {
            $trace = $this->getSession('stackTrace');
            
            $html ="\n<div onclick='if(this.style.height!=\"auto\")this.style.height=\"auto\";'  style='cursor:pointer;width:90%; margin:0 auto; margin-top:30px;margin-bottom:20px;text-align:left;border:2px solid black; background:#F1F1F1; font-size:15px; overflow:hidden;font-family:helvetica;height:25px;line-height:25px;padding:5px;'>Debug logs";
            
                if(isset($trace['apiRequest']))
                {
                    $html.="\n<div style='overflow:hidden;height:12px;border-bottom:2px dotted black; padding-bottom:5px;margin-top:10px; font-size:12px; line-height:14px;'><span style='font-size:14px; font-weight:bold;' onclick='if(this.parentNode.style.height!=\"auto\")this.parentNode.style.height=\"auto\"; else this.parentNode.style.height=\"12px\";'>";
                        $html.="API request trace history</span>\n";
                        foreach($trace['apiRequest'] as $req=>$apiRequest)$html.="<div style='margin:10px;'>This request is urldecoded:\n<textarea readonly='readonly' style='overflow:auto; width:100%; height:500px;margin-bottom:15px; border-bottom:3px solid black;' id='req-".$req."'>".str_replace("xml=","",urldecode($apiRequest))."</textarea></div>";
                    $html.='</div>';
                }
            
                if(isset($trace['response']))
                {
                    $html.="\n<div style='overflow:hidden;height:12px;border-bottom:2px dotted black; padding-bottom:5px;margin-top:10px; font-size:12px; line-height:14px;'><span style='font-size:14px; font-weight:bold;' onclick='if(this.parentNode.style.height!=\"auto\")this.parentNode.style.height=\"auto\"; else this.parentNode.style.height=\"12px\";'>";
                        $html.="API response trace history</span>\n";
                        foreach($trace['response'] as $res=>$response)$html.="<div style='margin:10px;'><textarea readonly='readonly' style='overflow:auto; width:100%;height:500px;margin-bottom:15px; border-bottom:3px solid black;' id='res-".$res."'>".$response."</textarea></div>";
                    $html.='</div>';
                }

                if(isset($trace['contextRequest']))
                {
                    $html.="\n<div style='overflow:hidden;height:12px;border-bottom:2px dotted black; padding-bottom:5px;margin-top:10px; font-size:12px; line-height:14px;'><span style='font-size:14px; font-weight:bold;' onclick='if(this.parentNode.style.height!=\"auto\")this.parentNode.style.height=\"auto\"; else this.parentNode.style.height=\"12px\";'>";
                        $html.="API context request trace history</span>\n";
                        foreach($trace['contextRequest'] as $reqc=>$contextRequest)$html.="<div style='margin:10px;'><textarea readonly='readonly' style='overflow:auto; width:100%;height:30px;margin-bottom:15px; border-bottom:3px solid black;' id='reqc-".$reqc."'>".$contextRequest."</textarea></div>";
                    $html.='</div>';
                }
            
            $html.="</div>\n"; 
            
            echo $html;
            $this->logShown=true;
            $this->addToSession('stackTrace','');
            $this->resetSession('stackTrace');
        }
        return true;
    }


}