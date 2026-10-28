<?php
require dirname(__DIR__).'/includes/site.php';
$address=bia_info('address','Rua Dr. Sobral Pinto, 425');
$district=bia_info('district','Vila Laroca');
$city=bia_info('city','Além Paraíba, MG');
$maps=bia_info('maps');
if($maps===''||!filter_var($maps,FILTER_VALIDATE_URL)||!str_starts_with($maps,'https://')){$maps='https://www.google.com/maps/search/?api=1&query='.rawurlencode($address.', '.$district.', '.$city);}
bia_header('Contato','Contato');
?>
<section class="contact-modern shell" aria-labelledby="contact-title">
 <div class="contact-modern-layout">
  <div class="contact-modern-info">
   <h2>Venha nos visitar.</h2><p class="contact-modern-intro">Uma boa comida e um ambiente acolhedor esperam por você.</p>
   <div class="contact-modern-list">
    <div class="contact-modern-item"><span class="contact-modern-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span><div><h3>Nosso endereço</h3><p><?=bia_esc($address)?><br><?=bia_esc($district)?> · <?=bia_esc($city)?></p><a href="<?=bia_esc($maps)?>" target="_blank" rel="noopener noreferrer">Ver rota no Google Maps <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div></div>
    <div class="contact-modern-item"><span class="contact-modern-icon"><i class="bi bi-clock" aria-hidden="true"></i></span><div><h3>Horário de atendimento</h3><p><?=bia_esc(bia_info('hours','Todos os dias · 10h30 às 15h'))?></p></div></div>
    <div class="contact-modern-item"><span class="contact-modern-icon"><i class="bi bi-telephone" aria-hidden="true"></i></span><div><h3>Telefone e WhatsApp</h3><p><?=bia_esc(bia_info('phone','(32) 98711-2340'))?></p></div></div>
   </div>
  </div>
  <div class="contact-modern-visual"><?php bia_photo('table','Salão do Restaurante da Bia preparado para receber visitantes','contact-modern-photo',true);?><a class="contact-modern-map" href="<?=bia_esc($maps)?>" target="_blank" rel="noopener noreferrer"><span><i class="bi bi-pin-map" aria-hidden="true"></i> Encontre a Bia no mapa</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
 </div>
</section>
<?php bia_footer();