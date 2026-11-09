<?php
/**
 * Left column of the attraction pages (view, view_two_way): breadcrumb, gallery, title,
 * favourite button, description and terms. Styles: theme.css (.attraction-detail).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction $attraction
 * @var array $auth_user
 * @var string $base Public host prefix for images.
 */
use App\Lib\PricingType;
use Cake\Core\Configure;

$typeLabels = [
    'settlement' => __('Open Date'),
    'fdate' => __('Fixed Date'),
    'fdatetime' => __('Fixed Date/Time'),
    PricingType::TWO_WAY_FDATETIME => __('2-Way Fixed Date/Time'),
];
$images = [];
if ($attraction->featured_image) {
    $images[] = $base . $attraction->featured_image;
}
foreach ((array)$attraction->attraction_medias as $media) {
    if (!empty($media->url)) {
        $images[] = $base . $media->url;
    }
}
$isFavorite = !empty($attraction->is_favorite);
if ($images) {
    // Lightbox2 (https://lokeshdhakar.com/projects/lightbox2/, MIT), local copy without bundled jQuery; options in ticket-row.js.
    $this->Html->css('/plugins/lightbox2/css/lightbox.min.css', ['block' => 'css']);
    $this->Html->script('/plugins/lightbox2/js/lightbox.min.js', ['block' => 'script']);
}
$title = (string)$attraction->title;
$favLabel = $isFavorite ? __('Click to remove from favorites') : __('Click to add to favorites');
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->Url->build('/') ?>"><?= __('Home') ?></a></li>
    <?php if ($attraction->has('category')): ?>
    <li class="breadcrumb-item"><?= $this->Html->link($attraction->category->name, ['controller' => 'Attractions', 'action' => 'index', '?' => ['categories' => [$attraction->category->id]]]) ?></li>
    <?php endif ?>
    <li class="breadcrumb-item active" aria-current="page"><?= h($attraction->title) ?></li>
  </ol>
</nav>

<div class="attraction-detail">
  <div class="card attraction-gallery" data-album-label="<?= h(__('Photo %1 of %2')) ?>">
    <?php if ($images): ?>
      <?php /* Main photo: a blurred cover copy fills the box behind a contained copy, so portrait/square photos show in full. */ ?>
      <a href="<?= h($images[0]) ?>" class="gallery-main" data-lightbox="gallery" data-title="<?= h($title) ?>" aria-label="<?= __('Open photo gallery') ?>">
        <img src="<?= h($images[0]) ?>" class="gallery-main-bg" alt="" aria-hidden="true">
        <img src="<?= h($images[0]) ?>" class="gallery-main-img" alt="<?= h($attraction->title) ?>">
        <span class="gallery-overlay" aria-hidden="true">
          <i class="fa fa-search-plus"></i>
          <span class="gallery-count"><?= count($images) ?> <?= count($images) === 1 ? __('Photo') : __('Photos') ?></span>
        </span>
      </a>
      <?php if (count($images) > 1): ?>
      <div class="card-body p-2">
        <div class="gallery-thumbnails-row">
          <?php foreach (array_slice($images, 1) as $i => $src): ?>
            <a href="<?= h($src) ?>" class="gallery-thumb-link" data-lightbox="gallery" data-title="<?= h($title) ?>" aria-label="<?= __('Photo {0}', $i + 2) ?>">
              <img src="<?= h($src) ?>" class="gallery-thumb-img" alt="" loading="lazy">
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif ?>
    <?php else: ?>
      <div class="gallery-main attraction-media-empty"><i class="fa fa-picture-o" aria-hidden="true"></i></div>
    <?php endif ?>
  </div>

  <div class="attraction-head">
    <div class="flex-grow-1 min-w-0">
      <h1 class="mt-4 mb-1"><?= h($attraction->title) ?></h1>
      <?php if (!empty($attraction->title_chinese)): ?><div class="attraction-title-zh"><?= $attraction->title_chinese ?></div><?php endif ?>
      <div class="d-flex flex-wrap gap-2 mt-2">
        <?php if (isset($typeLabels[$attraction->pricing_type])): ?>
          <span class="badge badge-soft"><i class="fa fa-ticket me-1" aria-hidden="true"></i><?= $typeLabels[$attraction->pricing_type] ?></span>
        <?php endif ?>
        <?php if (Configure::read('Default.show_attraction_sku') && $attraction->sku_id): ?>
          <span class="badge badge-soft-muted attraction-sku"><?= h($attraction->sku_id) ?></span>
        <?php endif ?>
      </div>
    </div>
    <?php if (($auth_user['role'] ?? null) != 'viewer'): ?>
      <button type="button"
              class="btn sg-fav-btn btn-favorite fa <?= $isFavorite ? 'fa-bookmark text-green' : 'fa-bookmark-o' ?>"
              data-attraction-id="<?= h($attraction->id) ?>"
              data-url="<?= $this->Url->build(['controller' => 'Attractions', 'action' => 'toggleFavorite']) ?>"
              data-label-on="<?= __('Click to remove from favorites') ?>"
              data-label-off="<?= __('Click to add to favorites') ?>"
              data-added="<?= __('Added to favourites') ?>"
              data-removed="<?= __('Removed from favourites') ?>"
              data-failed="<?= __('Could not update favourites. Please try again.') ?>"
              aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
              title="<?= $favLabel ?>" aria-label="<?= $favLabel ?>"></button>
    <?php endif ?>
  </div>

  <div class="attraction-description mw-100 overflow-hidden">
    <?= str_replace('<img src="../../images/', '<img src="' . $base . '/images/', (string)$attraction->description); ?>
  </div>

  <div class="accordion mt-4" id="accordion">
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
          <i class="fa fa-file-text-o me-2" aria-hidden="true"></i><?= __('Terms And Conditions') ?>
        </button>
      </h2>
      <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordion">
        <div class="accordion-body">
          <?= $attraction->terms_and_conditions; ?>
        </div>
      </div>
    </div>
  </div>
</div>

