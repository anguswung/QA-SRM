<?php
class ApiBaseController {
    protected $db;
    protected $user;
    public function __construct() {
        $this->db = DB::getInstance();
        $this->user = ApiAuth::getUser();
    }
    protected function user() { return $this->user; }
    protected function userId() { return (int)($this->user['user_id'] ?? 0); }
    protected function isAdmin() { return ($this->user['role'] ?? '') !== 'supplier'; }
    protected function isSupplier() { return ($this->user['role'] ?? '') === 'supplier'; }
    protected function supplierId() { return (int)($this->user['supplier_id'] ?? 0); }
}
