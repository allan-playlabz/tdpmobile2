<?php
/**
 * Attractions listing: search bar + filters (sticky sidebar on lg, offcanvas below) + card grid.
 * CSS: theme.css (.page-attractions-index). JS: webroot/js/pages/attractions-index.js.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Attraction[]|\Cake\Collection\CollectionInterface $attractions
 * @var array $categories
 * @var array $selected_categories
 * @var array $citiesByCountry
 * @var array $selected_cities
 * @var array $auth_user
 * @var array{name: string, slug: string, label: string}|null $currentCountry Set on /country/{slug}.
 */

$base = \Cake\Core\Configure::read('AWSPublic.public_host');
$sCategories = $selected_categories;
$selected_city_strings = array_map('strval', $selected_cities);
$queryParams = $this->request->getQueryParams();
$search = (string)$this->request->getQuery('search');
$currentCountry = $currentCountry ?? null;
// Every listing link (filters, chips, Show All) stays on /country/{slug} when browsing a country.
$listUrl = function (array $query = []) use ($currentCountry): array {
    return $currentCountry
        ? ['_name' => 'country', 'country' => $currentCountry['slug'], '?' => $query]
        : ['controller' => 'Attractions', 'action' => 'index', '?' => $query];
};
$countryCities = $currentCountry ? ($citiesByCountry[$currentCountry['name']] ?? []) : [];
$searchLabel = __('Name of Attractions');
if ($currentCountry) {
    $this->assign('title', __('Attractions in {0}', $currentCountry['label']));
    $searchLabel = __('Search attractions in {0}', $currentCountry['label']);
}

// Applied filters → chips (each links to the same listing without that filter).
$withoutValue = function (string $key, $value) use ($queryParams): array {
    $q = $queryParams;
    unset($q['page']);
    if (isset($q[$key]) && is_array($q[$key])) {
        $q[$key] = array_values(array_filter($q[$key], fn($v) => (string)$v !== (string)$value));
        if (!$q[$key]) {
            unset($q[$key]);
        }
    } else {
        unset($q[$key]);
    }
    return $q;
};
$chips = [];
if ($search !== '') {
    $chips[] = ['label' => '“' . $search . '”', 'query' => $withoutValue('search', null)];
}
foreach ($categories as $category) {
    if (in_array($category['id'], $sCategories)) {
        $chips[] = ['label' => $category['name'], 'query' => $withoutValue('categories', $category['id'])];
    }
    foreach ($category['child_categories'] as $child) {
        if (in_array($child['id'], $sCategories) && !in_array($category['id'], $sCategories)) {
            $chips[] = ['label' => $child['name'], 'query' => $withoutValue('categories', $child['id'])];
        }
    }
}
foreach ($citiesByCountry as $countryCities) {
    foreach ($countryCities as $city) {
        if (in_array((string)$city->id, $selected_city_strings, true)) {
            $chips[] = ['label' => $city->name, 'query' => $withoutValue('cities', $city->id)];
        }
    }
}
$filterCount = count($chips) - ($search !== '' ? 1 : 0);
$hasResults = !empty($attractions) && count($attractions) > 0;

$this->set('pageData', [
    'openAnnouncements' => ($auth_user['announcements_read'] ?? 1) == 0,
]);
$this->Html->script('pages/attractions-index.js', ['block' => 'script']);
?>
<?php if ($currentCountry): ?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->Url->build('/') ?>"><?= __('Home') ?></a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($currentCountry['label']) ?></li>
  </ol>
</nav>

<?php if ($countryCities): ?>
<section class="sg-destinations mb-4" aria-labelledby="top-destinations-title">
  <div class="d-flex align-items-baseline justify-content-between mb-3">
    <h1 class="h4 mb-0" id="top-destinations-title"><?= __('Top Destinations in {0}', h($currentCountry['label'])) ?></h1>
    <?php if ($selected_city_strings): ?>
      <?= $this->Html->link(__('View all'), $listUrl(), ['class' => 'small']) ?>
    <?php endif ?>
  </div>
  <div class="sg-dest-wrap">
    <button type="button" class="rec-nav sg-dest-nav sg-dest-prev d-none" aria-label="<?= __('Previous') ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
    <div class="sg-dest-row">
      <?php foreach (array_values($countryCities) as $cityIndex => $city): ?>
        <?php $isSelected = in_array((string)$city->id, $selected_city_strings, true); ?>
        <?php /* Light colour by position, so cities in one country never repeat a colour (12 colours). */ ?>
        <a class="sg-dest-tile sg-dest-c<?= $cityIndex % 12 ?><?= $isSelected ? ' is-selected' : '' ?>"
           href="<?= $this->Url->build($listUrl(['cities' => [$city->id]])) ?>"<?= $isSelected ? ' aria-current="true"' : '' ?>>
          <span class="sg-dest-name"><?= h($city->name) ?></span>
        </a>
      <?php endforeach ?>
    </div>
    <button type="button" class="rec-nav sg-dest-nav sg-dest-next d-none" aria-label="<?= __('Next') ?>"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
  </div>
</section>
<?php endif ?>
<?php endif ?>
<?php if (!empty($recommended_attractions)): ?>
<section class="recommended-section" aria-label="<?= __('Recommend') ?>">
  <div class="d-flex align-items-center mb-2">
    <span class="badge sg-recommend-badge"><i class="fa fa-star me-1" aria-hidden="true"></i><?= __('Recommend') ?></span>
  </div>
  <div class="recommended-carousel-wrap position-relative">
    <?php if (count($recommended_attractions) > 1): ?>
      <button type="button" class="rec-nav rec-prev" aria-label="<?= __('Previous') ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
      <button type="button" class="rec-nav rec-next" aria-label="<?= __('Next') ?>"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
    <?php endif ?>
    <div class="recommended-carousel">
      <div class="recommended-track">
        <?php foreach ($recommended_attractions as $attraction): ?>
        <div class="recommended-item">
          <div class="card attraction-card attraction-card-compact">
            <?= $this->element('attraction_card_compact', ['attraction' => $attraction, 'base' => $base]) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif ?>

<?= $this->Form->create(null, ['type' => 'get', 'url' => $listUrl(), 'id' => 'attraction-filters', 'class' => 'row g-4 py-2']) ?>
  <aside class="col-lg-3">
    <div class="offcanvas-lg offcanvas-start sg-filters" tabindex="-1" id="filtersPanel" aria-labelledby="filtersPanelLabel">
      <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h5 mb-0" id="filtersPanelLabel"><?= __('Filters') ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#filtersPanel" aria-label="<?= __('Close') ?>"></button>
      </div>
      <div class="offcanvas-body flex-column">
        <div class="search-categories w-100">
          <h2 class="sg-filter-title"><?= __('Categories') ?></h2>
          <div class="sg-filter-group" id="category-accordion">
            <?php $cardCount = 1; ?>
            <?php foreach ($categories as $key => $value) { ?>
            <div class="sg-filter-item">
              <div class="sg-filter-row" id="heading-<?= $cardCount ?>">
                <input class="form-check-input headclass headclass<?= $cardCount ?>" id="cat-<?= h($value['id']) ?>" data-headclass-id="<?= $cardCount ?>" name="categories[]" value="<?= h($value['id']) ?>" type="checkbox" <?= in_array($value['id'], $sCategories) ? 'checked' : '' ?> />
                <label class="form-check-label" for="cat-<?= h($value['id']) ?>"><?= h($value['name']) ?></label>
                <?php if (!empty($value['child_categories'])): ?>
                <button class="btn btn-link sg-filter-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $cardCount ?>" aria-expanded="false" aria-controls="collapse<?= $cardCount ?>" aria-label="<?= __('Show subcategories') ?>"><i class="fa fa-angle-down" aria-hidden="true"></i></button>
                <?php endif ?>
              </div>
              <div id="collapse<?= $cardCount ?>" class="collapse" aria-labelledby="heading-<?= $cardCount ?>" data-bs-parent="#category-accordion">
                <ul class="sg-filter-children">
                  <?php foreach ($value['child_categories'] as $ckey => $cvalue): ?>
                  <li>
                    <input class="form-check-input subclass subclass<?= $cardCount ?>" id="cat-<?= h($cvalue['id']) ?>" data-headclass-id="<?= $cardCount ?>" type="checkbox" name="categories[]" value="<?= h($cvalue['id']) ?>" <?= in_array($cvalue['id'], $sCategories) ? 'checked' : '' ?> />
                    <label class="form-check-label" for="cat-<?= h($cvalue['id']) ?>"><?= h($cvalue['name']) ?></label>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <?php
              $cardCount++;
              }
            ?>
          </div>
        </div>

        <div class="search-countries w-100 pt-4">
          <h2 class="sg-filter-title"><?= $currentCountry ? __('Cities') : __('Country') ?></h2>
          <div class="sg-filter-group country-checkboxes" id="country-accordion">
            <?php $countryIndex = 1; ?>
            <?php foreach ($citiesByCountry as $countryName => $countryCities): ?>
              <div class="sg-filter-item country-group">
                <div class="sg-filter-row" id="country-heading-<?= $countryIndex ?>">
                  <input class="form-check-input country-check" type="checkbox"
                         data-country-id="<?= $countryIndex ?>" id="country-<?= $countryIndex ?>" />
                  <label class="form-check-label fw-semibold" for="country-<?= $countryIndex ?>"><?= h($countryName) ?></label>
                  <button class="btn btn-link sg-filter-toggle<?= $currentCountry ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#country-collapse-<?= $countryIndex ?>" aria-expanded="<?= $currentCountry ? 'true' : 'false' ?>" aria-controls="country-collapse-<?= $countryIndex ?>" aria-label="<?= __('Show cities') ?>"><i class="fa fa-angle-down" aria-hidden="true"></i></button>
                </div>
                <div id="country-collapse-<?= $countryIndex ?>" class="collapse<?= $currentCountry ? ' show' : '' ?>" aria-labelledby="country-heading-<?= $countryIndex ?>" data-bs-parent="#country-accordion">
                  <ul class="sg-filter-children">
                    <?php foreach ($countryCities as $city): ?>
                      <li>
                        <input class="form-check-input city-check city-check-<?= $countryIndex ?>" type="checkbox"
                               data-country-id="<?= $countryIndex ?>"
                               name="cities[]" value="<?= h($city->id) ?>"
                               id="city-<?= $countryIndex ?>-<?= h($city->id) ?>"
                               <?= in_array((string)$city->id, $selected_city_strings, true) ? 'checked' : '' ?> />
                        <label class="form-check-label" for="city-<?= $countryIndex ?>-<?= h($city->id) ?>"><?= h($city->name) ?></label>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
              <?php $countryIndex++; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="sg-filter-actions w-100 mt-auto">
          <button type="submit" class="btn btn-cta w-100" id="btn-apply-filters"><i class="fa fa-filter me-2" aria-hidden="true"></i><?= __('Apply filters') ?></button>
          <?php if ($filterCount): ?>
            <?= $this->Html->link(__('Clear filters'), $listUrl(array_filter(['search' => $search])), ['class' => 'btn btn-link btn-sm w-100 mt-1']) ?>
          <?php endif ?>
        </div>
      </div>
    </div>
  </aside>

  <div class="col-lg-9">
    <div class="sg-searchbar mb-3">
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-white"><i class="fa fa-search text-muted" aria-hidden="true"></i></span>
        <?= $this->Form->text('search', ['class' => 'form-control', 'value' => $search, 'placeholder' => $searchLabel, 'aria-label' => $searchLabel]) ?>
        <button type="submit" class="btn btn-primary px-4"><?= __('Search') ?></button>
      </div>
      <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <button class="btn btn-outline-primary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtersPanel" aria-controls="filtersPanel">
          <i class="fa fa-sliders me-1" aria-hidden="true"></i><?= __('Filters') ?><?= $filterCount ? ' (' . $filterCount . ')' : '' ?>
        </button>
        <?php foreach ($chips as $chip): ?>
          <a class="sg-chip" href="<?= $this->Url->build($listUrl($chip['query'])) ?>" title="<?= __('Remove filter') ?>">
            <?= h($chip['label']) ?> <i class="fa fa-times" aria-hidden="true"></i><span class="visually-hidden"><?= __('Remove filter') ?></span>
          </a>
        <?php endforeach; ?>
        <?= $this->Html->link(__('Show All'), $listUrl(), ['class' => 'btn btn-link btn-sm' . ($chips ? '' : ' text-muted')]) ?>
        <?php if ($hasResults): ?>
          <span class="ms-auto small text-muted"><?= $this->Paginator->counter(__('{{count}} attractions')) ?></span>
        <?php endif ?>
      </div>
    </div>

    <?php if ($hasResults): ?>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-4 g-3">
        <?php foreach ($attractions as $attraction): ?>
        <div class="col">
          <div class="card attraction-card h-100">
            <?= $this->element('attraction_card', ['attraction' => $attraction, 'base' => $base]) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <nav class="paginator mt-4" aria-label="<?= __('Pages') ?>">
          <ul class="pagination justify-content-center">
              <?= $this->Paginator->first('« ' . __('first')) ?>
              <?= $this->Paginator->prev('‹ ' . __('previous')) ?>
              <?= $this->Paginator->numbers() ?>
              <?= $this->Paginator->next(__('next') . ' ›') ?>
              <?= $this->Paginator->last(__('last') . ' »') ?>
          </ul>
          <p class="text-center small text-muted"><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
      </nav>
    <?php else: ?>
      <div class="sg-empty text-center py-5">
        <i class="fa fa-map-signs" aria-hidden="true"></i>
        <p class="lead mb-1"><?= __('No attractions found.') ?></p>
        <p class="text-muted"><?= __('Try another name or remove some filters.') ?></p>
        <?= $this->Html->link(__('Clear filters'), $listUrl(), ['class' => 'btn btn-outline-primary']) ?>
      </div>
    <?php endif ?>
  </div>
<?= $this->Form->end() ?>
