<?php
function _curl_get_with_proxy($url) {

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  $ret = curl_exec($ch);
  if (curl_errno($ch)) {
      return NULL;
  }
  curl_close($ch);
  
  return $ret;
}

function _get_gzbn_broadcast_list() {
  $prog_list = 'https://channel.gztv.com/channelf/site/rest/tv-channel/getChannelAndProgramList';
  
  $prog_list_data = json_decode(_curl_get_with_proxy($prog_list), 1);
  $channels = [];

  foreach ($prog_list_data['data'] ?? [] as $v) {
    $channels[] = [
      'name' => $v['name'],
      'uuid' => $v['uuid'],
    ];
  }
  
  return $channels;
}

function _jump_gzbn_broadcast_m3u8($channel) {
  $video_page_api = 'https://channel.gztv.com/channelf/viewapi/player/channelVideo?id=%s&commentFrontUrl=https://comment.gztv.com/commentf';
  
  $video_page_url = sprintf($video_page_api, $channel['uuid']);
  $video_page_data = _curl_get_with_proxy($video_page_url);

  $m3u8_uri = NULL; $secondid = NULL;
  if (preg_match('/standardUrl=\'(.*)\';/', $video_page_data, $matches)) $m3u8_uri = $matches[1];
  if (preg_match('/secondId=\'(.*)\';/', $video_page_data, $matches)) $secondid = $matches[1];

  header("Location: " . $m3u8_uri);
}

if (isset($_GET['all_channels']) && intval($_GET['all_channels']) == 1) {
  $channels = _get_gzbn_broadcast_list();
  return ;
}

$all_channels = _get_gzbn_broadcast_list();
$channel_names = [
  'general' => '综合',
  'news' => '新闻',
  'drama' => '影视',
  'sport' => '竞赛',
  'legal' => '法治',
  'uhd' => '南国都市',
];
if (isset($_GET['get_channel']) && isset($channel_names[$_GET['get_channel']])) {
  $needle = $channel_names[$_GET['get_channel']];
  foreach ($all_channels as $channel) {
    if (strpos($channel['name'], $needle) !== false) {
      _jump_gzbn_broadcast_m3u8($channel);
      return ;
    }
  }
}

http_response_code(404);
die();
?>