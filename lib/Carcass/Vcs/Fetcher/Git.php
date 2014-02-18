<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Vcs;

use Carcass\Fs;

class Fetcher_Git extends Fetcher {

    protected $git_bin = 'git';

    public function execCheckout($full = false) {
        if ($full) {
            $this->checkoutFull();
        } else {
            $this->checkoutPartial();
        }
    }

    protected function checkoutPartial() {
        Fs\Directory::mkdirIfNotExists($this->local_root);
        return $this->exec(
            $this->git_bin,
            'init',
            [],
            $this->local_root
        ) && $this->exec(
            $this->git_bin,
            'remote add origin {{ source }}',
            [
                'source' => $this->repository_url,
            ],
            $this->local_root
        ) && $this->exec(
            $this->git_bin,
            'fetch --depth 1 origin {{ origin }}',
            [
                'origin' =>  $this->revision ? : $this->branch ? : 'HEAD'
            ],
            $this->local_root
        ) && $this->exec(
            $this->git_bin,
            'reset --hard FETCH_HEAD',
            [],
            $this->local_root
        );
    }

    protected function checkoutFull() {
        return $this->exec(
            $this->git_bin,
            'clone {{ IF branch }}-b {{ branch }} {{ END }}{{ source }} {{ target }}',
            [
                'source' => $this->repository_url,
                'target' => $this->local_root,
                'branch' => $this->branch,
            ]
        );
    }

    public function execUpdate() {
        return $this->exec(
            $this->git_bin,
            'pull{{ IF revision }} origin {{ revision }}{{ END }}',
            [
                'revision' => $this->revision,
            ],
            true
        ) and $this->exec(
            $this->git_bin,
            'checkout{{ IF revision }} {{ revision }}{{ END }}',
            [
                'revision' => $this->revision,
            ],
            true
        );
    }

    public function setVcsBinary($hg_bin) {
        $this->git_bin = (string)$hg_bin;
        return $this;
    }

    protected function compareConfiguration(array $cfg) {
        return parent::compareConfiguration($cfg) && $cfg['branch'] === $this->branch;
    }

    protected function getConfiguration() {
        return parent::getConfiguration() + ['branch' => $this->branch];
    }

    public function getRevision() {
        throw new \LogicException("Not implemented"); // todo
    }

    public function getRevisionTimestamp() {
        throw new \LogicException("Not implemented"); // todo
    }
}