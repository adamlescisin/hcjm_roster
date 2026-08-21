<?php
/**
 * Hooks loader – registers all actions and filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HCJM_Loader {

    /** @var array<array{hook:string,component:mixed,callback:string,priority:int,args:int}> */
    private array $actions = [];

    /** @var array<array{hook:string,component:mixed,callback:string,priority:int,args:int}> */
    private array $filters = [];

    public function add_action( string $hook, $component, string $callback, int $priority = 10, int $args = 1 ): void {
        $this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'args' );
    }

    public function add_filter( string $hook, $component, string $callback, int $priority = 10, int $args = 1 ): void {
        $this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'args' );
    }

    public function run(): void {
        foreach ( $this->filters as $f ) {
            $callable = $f['component'] === null
                ? $f['callback']
                : [ $f['component'], $f['callback'] ];
            add_filter( $f['hook'], $callable, $f['priority'], $f['args'] );
        }
        foreach ( $this->actions as $a ) {
            $callable = $a['component'] === null
                ? $a['callback']
                : [ $a['component'], $a['callback'] ];
            add_action( $a['hook'], $callable, $a['priority'], $a['args'] );
        }
    }
}
