<?php

return [
    /**
     * Use uuid as primary key.
     */
    'uuids' => false,

    /*
     * User tables foreign key name.
     */
    'user_foreign_key' => 'user_id',

    /**
     * If uses table use uuid as primary, please set to true.
     */
    'users_use_uuids' => false,

    /*
     * Table name for vote records.
     */
    'votes_table' => 'votes',

    /*
     * Model name for Vote record.
     */
    'vote_model' => \Overtrue\LaravelVote\Vote::class,
];
