<?php

declare(strict_types=1);

namespace App;

use DomainException;

final class Cms
{
    /**
     * Campos editáveis realmente presentes nas páginas públicas.
     * O segundo elemento é o texto ORIGINAL usado pelo tema para substituição.
     * Ao adicionar um texto, registre-o aqui com uma chave estável e um rótulo humano.
     */
    public static function definitions(): array
    {
        return [
            'inicio' => [
                'label' => 'Início',
                'fields' => [
                    'hero_eyebrow' => ['Chamada da capa', 'O sabor de se sentir em casa'],
                    'hero_title' => ['Título principal da capa', 'Tempero de casa.'],
                    'hero_subtitle' => ['Destaque da capa', 'Sabor de verdade.'],
                    'hero_description' => ['Descrição da capa', 'Comida mineira, variedade todos os dias e aquele carinho que transforma o almoço em um momento especial.'],
                    'hero_button' => ['Botão da capa', 'Conheça nosso cardápio'],
                    'hours_eyebrow' => ['Chamada do horário', 'Venha almoçar com a gente'],
                    'hours_title' => ['Título do horário', 'Nosso horário de atendimento'],
                    'featured_eyebrow' => ['Chamada dos sabores', 'Escolha o seu favorito'],
                    'featured_title' => ['Título dos sabores', 'Uma mesa cheia de sabores.'],
                    'featured_description' => ['Descrição dos sabores', 'Da tradição mineira às opções mais leves, aqui sempre tem algo gostoso esperando por você.'],
                    'feature_meat' => ['Categoria: comida mineira', 'Comida mineira'],
                    'feature_salad' => ['Categoria: saladas', 'Saladas & acompanhamentos'],
                    'feature_pasta' => ['Categoria: massas', 'Massas & especiais'],
                    'about_eyebrow' => ['Chamada sobre o restaurante', 'Muito além do almoço'],
                    'about_title' => ['Título sobre o restaurante', 'Um lugar para se reunir e saborear.'],
                    'about_description' => ['Texto sobre o restaurante', 'Ambiente amplo, climatizado e acolhedor, com espaço para 144 pessoas. Sinta-se à vontade: a casa é sua.'],
                    'order_eyebrow' => ['Chamada para pedir', 'SEU ALMOÇO, DO SEU JEITO'],
                    'order_title' => ['Título da seção de pedidos', 'Comida de verdade.'],
                    'order_subtitle' => ['Destaque da seção de pedidos', 'Do nosso fogão para você.'],
                    'order_description' => ['Descrição da seção de pedidos', 'Escolha os pratos do dia, monte sua quentinha e aproveite o sabor da Bia onde estiver.'],
                    'order_button' => ['Botão principal de pedidos', 'Montar minha quentinha'],
                ],
                'images' => [
                    'hero' => 'Imagem de capa',
                    'meat' => 'Comida mineira e seção de pedidos',
                    'salad' => 'Saladas',
                    'pasta' => 'Massas',
                    'interior' => 'Interior do restaurante',
                ],
            ],
            'cardapio' => [
                'label' => 'Cardápio',
                'fields' => [
                    'menu_eyebrow' => ['Chamada do cardápio', 'COMIDA FEITA COM CARINHO'],
                    'menu_title' => ['Título do cardápio', 'Cardápio do dia'],
                    'menu_notice' => ['Aviso sobre itens por peso', 'Itens com * são vendidos por peso e não entram nas quentinhas.'],
                    'menu_button' => ['Botão de pedido', 'Monte sua quentinha'],
                ],
                'images' => [],
            ],
            'o-restaurante' => [
                'label' => 'O Restaurante',
                'fields' => [
                    'about_eyebrow' => ['Chamada da história', 'O Restaurante da Bia'],
                    'about_title' => ['Título da história', 'O sabor da vida está em quem tempera.'],
                    'about_description' => ['Apresentação do restaurante', 'No Restaurante da Bia, o almoço vai além do prato. Nosso espaço é amplo, bem iluminado e climatizado, com 144 lugares para receber famílias, amigos e quem valoriza uma refeição feita com cuidado.'],
                    'food_description' => ['Variedade de pratos', 'Diariamente, oferecemos uma grande variedade de acompanhamentos quentes, saladas, comida mineira, massas, carnes e sobremesas.'],
                    'gallery_button' => ['Botão para galeria', 'Conheça nossos espaços'],
                ],
                'images' => [
                    'interior' => 'Fotografia do restaurante',
                ],
            ],
            'galeria' => [
                'label' => 'Galeria',
                'fields' => [
                    'gallery_eyebrow' => ['Chamada da galeria', 'Um pouco da nossa casa'],
                    'gallery_title' => ['Título da galeria', 'Sabores, encontros e momentos.'],
                    'gallery_description' => ['Descrição da galeria', 'Um espaço para conhecer o clima do restaurante. As imagens atuais são demonstrativas e serão substituídas por fotografias reais.'],
                    'gallery_cta_title' => ['Título do convite', 'Venha conhecer de perto.'],
                    'gallery_cta_description' => ['Texto do convite', 'Estamos esperando por você em Além Paraíba.'],
                    'gallery_cta_button' => ['Botão do convite', 'Como chegar'],
                ],
                'images' => [],
            ],
            'contato' => [
                'label' => 'Contato',
                'fields' => [
                    'contact_title' => ['Título da página', 'Venha nos visitar.'],
                    'contact_description' => ['Descrição da página', 'Uma boa comida e um ambiente acolhedor esperam por você.'],
                    'address_title' => ['Título do endereço', 'Nosso endereço'],
                    'hours_title' => ['Título dos horários', 'Horário de atendimento'],
                    'phone_title' => ['Título do telefone', 'Telefone e WhatsApp'],
                ],
                'images' => [
                    'table' => 'Foto do restaurante',
                ],
            ],
        ];
    }

    public static function field(string $page, string $key, string $default = ''): string
    {
        static $cache = [];
        $id = "$page.$key";
        if (array_key_exists($id, $cache)) {
            return $cache[$id];
        }
        try {
            $q = Db::pdo()->prepare('SELECT field_value FROM cms_fields WHERE page_slug=? AND field_key=?');
            $q->execute([$page,$key]);
            $v = $q->fetchColumn();
            return $cache[$id] = $v === false ? $default : (string)$v;
        } catch (\PDOException $e) {
            error_log((string)$e);
            return $default;
        }
    }
    public static function setting(string $key): string
    {
        try {
            $q = Db::pdo()->prepare('SELECT setting_value FROM cms_settings WHERE setting_key=?');
            $q->execute([$key]);
            return (string)($q->fetchColumn() ?: '');
        } catch (\PDOException $e) {
            error_log((string)$e);
            return '';
        }
    }
    public static function image(string $page, string $slot): string
    {
        return self::field($page, 'image_'.$slot, '');
    }
    public static function saveField(string $page, string $key, string $value): void
    {
        $q = Db::pdo()->prepare('INSERT INTO cms_fields(page_slug,field_key,field_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value)');
        $q->execute([$page,$key,$value]);
    }
    public static function upload(array $file, string $prefix): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new DomainException('Envio de imagem inválido');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || ($file['size'] ?? 0) < 1) {
            throw new DomainException('Imagem deve ter até 5 MB');
        }
        $info = @getimagesize($file['tmp_name']);
        $mime = $info['mime'] ?? '';
        $types = ['image/jpeg' => 'jpg','image/png' => 'png','image/webp' => 'webp'];
        if (!isset($types[$mime]) || !isset($info[0],$info[1]) || $info[0] > 6000 || $info[1] > 6000) {
            throw new DomainException('Use JPG, PNG ou WebP de até 6000 pixels');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($file['tmp_name']) !== $mime) {
            throw new DomainException('Tipo de imagem inconsistente');
        }
        $dir = dirname(__DIR__).'/public/assets/images/bia/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new DomainException('Não foi possível criar pasta de uploads');
        }
        $name = $prefix.'-'.bin2hex(random_bytes(12)).'.'.$types[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir.'/'.$name)) {
            throw new DomainException('Pasta de imagens sem permissão de escrita');
        }
        return '/assets/images/bia/uploads/'.$name;
    }
    public static function asset(string $url): string
    {
        if (!preg_match('#^/assets/images/bia/uploads/[a-zA-Z0-9_-]+\.(jpg|png|webp)$#', $url)) {
            throw new DomainException('Imagem inválida');
        }return $url;
    }
    public static function gallery(): array
    {
        return Db::pdo()->query('SELECT * FROM cms_gallery ORDER BY sort_order ASC,id DESC')->fetchAll();
    }
}
