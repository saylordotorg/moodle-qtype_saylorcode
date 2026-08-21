<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_saylorcode;

use local_saylorcode\local\runner\execution_request;
use local_saylorcode\local\runner\execution_response;
use local_saylorcode\local\runner\execution_state;
use local_saylorcode\local\runner\health_result;
use local_saylorcode\local\runner\provider_interface;

/**
 * A runner that answers from a script instead of executing anything.
 *
 * Grading is the behaviour under test, not compilation. This makes the runner's
 * answers -- including its failures, which are the interesting cases -- something
 * a test can state outright.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scripted_provider implements provider_interface {
    /** @var array Queued stdout values, one per execution. */
    public $outputs = [];

    /** @var string The state to report. */
    public $state = execution_state::COMPLETED;

    /** @var bool Whether execute() should throw, as a transport failure would. */
    public $throw = false;

    /** @var int How many executions were asked for. */
    public $calls = 0;

    /**
     * Build a scripted runner.
     *
     * @param array $outputs stdout for each successive execution.
     */
    public function __construct(array $outputs = []) {
        $this->outputs = $outputs;
    }

    /**
     * The runner's name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'scripted';
    }

    /**
     * Health, which these tests do not exercise.
     *
     * @return health_result
     */
    public function get_health(): health_result {
        return new health_result(true, 'scripted');
    }

    /**
     * The profiles this runner claims.
     *
     * @return array
     */
    public function get_supported_profiles(): array {
        return ['java17-console'];
    }

    /**
     * Answer from the script.
     *
     * @param execution_request $request The request.
     * @return execution_response
     */
    public function execute(execution_request $request): execution_response {
        $this->calls++;

        if ($this->throw) {
            throw new \moodle_exception('runner is unreachable');
        }

        $stdout = array_shift($this->outputs) ?? '';

        return new execution_response(
            $request->get_request_id(),
            $this->state,
            $stdout,
            '',
            '',
            [],
            0,
            0.0,
            0.0
        );
    }

    /**
     * Cancellation, which these tests do not exercise.
     *
     * @param string $requestid The request.
     * @return bool
     */
    public function cancel(string $requestid): bool {
        return false;
    }

    /**
     * Whether cancellation is offered.
     *
     * @return bool
     */
    public function supports_cancellation(): bool {
        return false;
    }
}
