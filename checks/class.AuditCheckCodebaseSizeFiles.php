<?php

class AuditCheckCodebaseSizeFiles extends AuditCheck {
  protected $_size_mb;

  public function getLabel() {
    return dt('Size of sites/default/files');
  }

  public function getResultFail() {
    return dt('Unable to determine size of sites/default/files!');
  }

  public function getResultInfo() {
    return dt('Size: @size_in_mbMB', array(
      '@size_in_mb' => number_format($this->_size_mb),
    ));
  }

  public function getResultPass() {}

  public function getResultWarning() {}

  public function getAction() {}

  public function getDescription() {
    return dt('Determine the size of sites/default/files.');
  }

  public function getScore() {
    $drupal_root = drush_get_context('DRUSH_SELECTED_DRUPAL_ROOT');
    exec('du -s -k -x ' . $drupal_root . '/sites/default/files', $result);
    $kb_size_files = trim($result[0]);
    $this->_size_mb = round($kb_size_files / 1024, 2);
    if (!$this->_size_mb) {
      $this->abort = TRUE;
      return AuditCheck::AUDIT_CHECK_SCORE_FAIL;
    }
    return AuditCheck::AUDIT_CHECK_SCORE_INFO;
  }
}
