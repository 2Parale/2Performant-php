<?php

namespace TPerformant\API\HTTP;

/**
 * Class used to retrieve a saved session (by its tokens)
 */
class SavedSession implements AuthInterface {
    private $accessToken;
    private $clientToken;
    private $uid;

    /**
     * @param string $accessToken Saved access token
     * @param string $clientToken Saved client token
     * @param string $uid         Saved user ID
     */
    public function __construct($accessToken, $clientToken, $uid) {
        $this->accessToken = $accessToken;
        $this->clientToken = $clientToken;
        $this->uid = $uid;
    }

    /**
     * Access token getter
     * @return string Access token
     */
    public function getAccessToken() {
        return $this->accessToken;
    }

    /**
     * Client token getter
     * @return string Client token
     */
    public function getClientToken() {
        return $this->clientToken;
    }

    /**
     * User ID getter
     * @return string User ID
     */
    public function getUid() {
        return $this->uid;
    }
}
