<?php
declare(strict_types=1);
namespace App;
final class GoogleReviews {
 public static function load():array {
  $key=trim((string)($_ENV['GOOGLE_PLACES_API_KEY']??''));
  $id=trim((string)($_ENV['GOOGLE_PLACE_ID']??''));
  $empty=['reviews'=>[],'maps_url'=>'https://www.google.com/maps/search/?api=1&query=Restaurante%20da%20Bia%20Al%C3%A9m%20Para%C3%ADba'];
  if(!$key||!$id||!preg_match('/^[a-zA-Z0-9_-]{8,180}$/',$id))return $empty;
  $url='https://places.googleapis.com/v1/places/'.rawurlencode($id).'?languageCode=pt-BR';
  $headers=['X-Goog-Api-Key: '.$key,'X-Goog-FieldMask: googleMapsUri,reviews'];
  // API content is fetched per request; do not persist Google review content in MySQL.
  $context=stream_context_create(['http'=>['method'=>'GET','header'=>implode("\r\n",$headers),'timeout'=>4,'ignore_errors'=>true]]);
  $raw=@file_get_contents($url,false,$context);
  $status=$http_response_header[0]??'';
  if($raw===false||!str_contains($status,' 200 ')){error_log('Google Places review request failed: '.substr($status,0,80));return $empty;}
  $data=json_decode($raw,true);
  if(!is_array($data))return $empty;
  $maps=(string)($data['googleMapsUri']??$empty['maps_url']);
  if(!str_starts_with($maps,'https://www.google.com/maps/'))$maps=$empty['maps_url'];
  $reviews=[];
  foreach(($data['reviews']??[]) as $r){
   if((int)($r['rating']??0)!==5)continue;
   $text=trim((string)($r['text']['text']??$r['originalText']['text']??''));
   if($text==='')continue;
   $author=$r['authorAttribution']??[];
   $link=(string)($r['googleMapsUri']??$maps);
   if(!str_starts_with($link,'https://www.google.com/maps/'))$link=$maps;
   $avatar=(string)($author['photoUri']??'');
   if($avatar&&!str_starts_with($avatar,'https://'))$avatar='';
   $reviews[]=['text'=>$text,'name'=>(string)($author['displayName']??'Cliente Google'),'date'=>(string)($r['relativePublishTimeDescription']??''),'avatar'=>$avatar,'url'=>$link];
  }
  return ['reviews'=>array_slice($reviews,0,5),'maps_url'=>$maps];
 }
}
