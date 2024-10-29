<?php

/*******************************************************************************
** Allows user to specify a shortcode in the page/post to include the case 
** studies widget. 
**
** Expected format:
**
** [displaycount]
**
*******************************************************************************/

	 	//[add short code ability]
		function show_AW_count($atts) {
    global $wp, $wpdb;
    
    extract(
    	shortcode_atts(
    		array(
    			'list' => '',    			
    		), 
    		$atts
    	)
    );
	   
	   
	   global $wp, $wpdb;
	   global $tgm_aw_options, $tgm_aw_Count_data;
	  /* echo '<pre>';
	  print_r($tgm_aw_options); exit;*/
	   
	   $list=explode(",",$list);
		//echo '<pre>'; print_r($acc);
		//print_r($tgm_aw_options); die;
		if(count($list)<=0){
			$subscriber_count=getListCount($tgm_aw_options['current_list_name']);
		} else {
			//print_r($list);
			$subscriber_count=0; //initially set the subscriber count to 0;
			foreach ($list as $l )
			{
				$subscriber_count += (int)getListCount($l); 
			}
			
		}
		return $subscriber_count;
}

function getListCount($list_name='')
{
		global $option;
		global $wp, $wpdb;
		global $tgm_aw_options, $tgm_aw_Count_data;	
		
		if($list_name!='')
		{
			for($i=0; $i<count($tgm_aw_options['lists']); $i++)
			{
				if($tgm_aw_options['lists'][$i]['name']==$list_name)
				{
					$diff=time() - $tgm_aw_options['lists'][$i]['last_updated'];
					
					if($tgm_aw_options['lists'][$i]['real_subscribers']=='' or $tgm_aw_options['lists'][$i]['total_subscribers']=='' or $diff>=$tgm_aw_options['cache_validity'] ) // if listcount is not present in the cache then fetch from aweber
					{
						
						if ( ! ( empty( $tgm_aw_options['auth_key'] ) && empty( $tgm_aw_options['auth_token'] ) && empty( $tgm_aw_options['req_key'] ) 
								&& empty( $tgm_aw_options['req_token'] ) && empty( $tgm_aw_options['oauth'] ) && empty( $tgm_aw_options['user_token'] ) 
								&& empty( $tgm_aw_options['user_token_secret'] ) ) ) 
						{
							/** Everything is set in our options, so let's move forward */
							require_once('lib/aweber_api/aweber_api.php');
							$aweber = new AWeberAPI( $tgm_aw_options['auth_key'], $tgm_aw_options['auth_token'] );
							$aweber->user->requestToken = $tgm_aw_options['req_key'];
							$aweber->user->tokenSecret = $tgm_aw_options['req_token'];
							$aweber->user->verifier = $tgm_aw_options['oauth'];
							try 
							{			
								$user_account = $aweber->getAccount( $tgm_aw_options['user_token'], $tgm_aw_options['user_token_secret'] );						
								$acc = $user_account->lists->data;
								for ($j = 0; $j< count($acc['entries']); $j++)
								{
									
									if ($acc['entries'][$j]['name']==$list_name ) //if listname matches the current list
									{
										$tgm_aw_options['lists'][$i]['real_subscribers']=(int)$acc['entries'][$j]['total_subscribers'];
										$tgm_aw_options['lists'][$i]['total_subscribers']=(int)$acc['entries'][$j]['total_subscribed_subscribers'];
										$tgm_aw_options['lists'][$i]['last_updated']=time();
										
										update_option( 'tgm_aweber_Count_settings', $tgm_aw_options );
										
										
									}
									
									//echo $value;
								}
							}
							catch ( AWeberException $e ) {
								return false;
							}
							
						}
					}
					
					if( $tgm_aw_options['inc_unsubscribed']=='1' ) //check if options for include unsubscribers in count is ON
						return $tgm_aw_options['lists'][$i]['total_subscribers'];
					else
						return $tgm_aw_options['lists'][$i]['real_subscribers'];
				}
			}
			
		}
		else
		{
			return "EMPTY LIST";
		}
		
}
add_shortcode('displaycount','show_AW_count',1); 
add_filter('widget_text', 'do_shortcode');

		


