<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Vcs;

class Fetcher_Hg extends Fetcher {

    protected $hg_bin = 'hg';

    public function execCheckout() {
        return $this->exec(
            $this->hg_bin,
            'clone {{ source }} {{ target }}',
            [
                'source' => $this->repository_url,
                'target' => $this->local_root,
            ]
        );
    }

    public function execUpdate() {
        return $this->exec(
            $this->hg_bin,
            'pull',
            [],
            true
        ) and $this->exec(
            $this->hg_bin,
            'up{{ IF branch }} -C {{ branch }}{{ END }}{{ IF revision }} -r {{ revision }}{{ END }}',
            [
                'branch'   => $this->branch,
                'revision' => $this->revision,
            ],
            true
        );
    }

    public function setVcsBinary($hg_bin) {
        $this->hg_bin = (string)$hg_bin;
        return $this;
    }

}