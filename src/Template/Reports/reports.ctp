<?php $this->start('title') ?>
Downloadable Reports
<?php $this->end() ?>
<?= $this->Html->link(__('Back'), ['controller' => 'Reports', 'action' => 'index']); ?>

<h4 class="mb-4">Reports</h4>
<table class="table table-striped">
    <tr>
        <th>Requested Date/Time</th>
        <th>Download</th>
    </tr>
    <?php if (!empty($processing_files) || !empty($reports)) { ?>
    <?php if ($processing_files_count > 0) { ?>
        <?php foreach ($processing_files as $processing_file) { ?>
            <tr>

                <td>
                    <?= $processing_file['created'] ?>
                </td>
                <td>Processing</td>
            </tr>
        <?php } ?>
    <?php } ?>
    <?php foreach ($reports as $created => $report) { ?>
        <tr>
            <td>
                <?= $report['created'] ?>
            </td>
            <td>
                <?php echo $this->Form->postLink($report['name'], ['controller' => 'Reports', 'action' => 'download', $report['filename']]) ?>
            </td>

        </tr>
    <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="2"><div class="alert alert-danger"><?= __('No reports available.') ?></div></td>
        </tr>
    <?php } ?>
</table>
