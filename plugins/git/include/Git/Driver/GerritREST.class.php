<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I know how to speak to a Gerrit 2.8+ remote server
 */
class Git_Driver_GerritREST implements Git_Driver_Gerrit {

    const CONTENT_TYPE_JSON = 'Content-Type: application/json;charset=UTF-8';

    /** @var Http_Client */
    private $http_client;

    /** @var Logger */
    private $logger;

    /** @var Git_Driver_GerritRESTBodyBuilder */
    private $body_builder;

    public function __construct(
        Http_Client $http_client,
        Logger $logger,
        Git_Driver_GerritRESTBodyBuilder $body_builder
    ) {
        $this->http_client  = $http_client;
        $this->logger       = $logger;
        $this->body_builder = $body_builder;
    }

    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name
    ) {
        $gerrit_project_name = $this->getGerritProjectName($repository);

        $this->http_client->init();
        $this->logger->info("Gerrit REST driver: Create project $gerrit_project_name");

        $url       = '/projects/'. urlencode($gerrit_project_name);
        $json_data = json_encode(
            array(
                'description' => "Migration of $gerrit_project_name from Tuleap",
                'parent'      => $parent_project_name
            )
        );

        $custom_options = array(
            CURLOPT_PUT        => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_INFILE     => $this->body_builder->getTemporaryFile($json_data),
            CURLOPT_INFILESIZE => strlen($json_data),
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            if (! $this->http_client->isLastResponseSuccess()) {
                throw new Git_Driver_Gerrit_Exception(
                    'url: '. $this->getGerritURL($server, $url).
                    ', http code: '. $this->http_client->getLastHTTPCode()
                );
            }

            $this->logger->info("Gerrit: Project $gerrit_project_name successfully initialized");
            return $gerrit_project_name;
        } catch (Http_ClientException $exception) {
            $this->logger->error("Gerrit: Project $gerrit_project_name not created: " . $exception->getMessage());
            return false;
        }
    }

    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name
     ){
        $parent_project_name = $project->getUnixName();

        $this->http_client->init();
        $this->logger->info("Gerrit REST driver: Create parent project $parent_project_name");

        $url       = '/projects/'.urlencode($parent_project_name);
        $json_data = json_encode(
            array(
                'description'      => "Migration of $parent_project_name from Tuleap",
                'permissions_only' => true,
                'owners'           => array(
                    $admin_group_name
                )
            )
        );

        $custom_options = array(
            CURLOPT_PUT        => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_INFILE     => $this->body_builder->getTemporaryFile($json_data),
            CURLOPT_INFILESIZE => strlen($json_data),
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            $this->logger->info("Gerrit: Permissions-only project $parent_project_name successfully initialized");
        } catch (Http_ClientException $exception) {
            $this->logger->error(var_export($this->http_client->getLastRequest(), true));
            $this->logger->error("Gerrit: Permissions-only project $parent_project_name not created: " . $exception->getMessage());
            return false;
        }
    }

    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name ){
       return $this->doesTheProjectExist($server, $project_name);
    }

    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name ){
        $this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Check if project $project_name already exists");

        $url            = '/projects/'.urlencode($project_name);
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST => 'GET'
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            $this->logger->debug("Gerrit REST driver: project $project_name exists");

            return true;
        } catch (Http_ClientException $exception) {
            $this->logger->debug("Gerrit REST driver: project $project_name does not exist");

            return false;
        }
    }

    public function ping(Git_RemoteServer_GerritServer $server ){
        //$this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Check if server is up");

        $url            = '/config/server/version';
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST => 'GET'
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->http_client->isLastResponseSuccess();
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function listParentProjects(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner ){
        if ($this->doesTheGroupExist($server, $group_name)) {
            return;
        }

        $this->http_client->init();
        $this->logger->info("Gerrit REST driver: Create group $group_name");

        $url            = '/groups/'.urlencode($group_name);
        $custom_options = array(
            CURLOPT_PUT => true,
        );

        if ($group_name !== $owner) {

            $json_data = json_encode(
                array(
                    'owner' => $owner
                )
            );
            $custom_options[CURLOPT_HTTPHEADER] = array(self::CONTENT_TYPE_JSON);
            $custom_options[CURLOPT_INFILE]     = $this->body_builder->getTemporaryFile($json_data);
            $custom_options[CURLOPT_INFILESIZE] = strlen($json_data);
        }

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            $this->logger->info("Gerrit REST driver: Group $group_name successfully created");
        } catch (Http_ClientException $exception) {
            $this->logger->error("Gerrit REST driver: Unable to create group $group_name");
            return false;
        }
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name ){
        $group_info = $this->getGroupInfoFromGerrit($server, $group_full_name);
        if (! $group_info) {
            return;
        }

        return $group_info['id'];
    }

    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name ){
        $group_info = $this->getGroupInfoFromGerrit($server, $group_full_name);
        if (! $group_info) {
            return;
        }

        return $group_info['group_id'];
    }

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name ){
        $this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Check if the group $group_name exists");

        $this->getGroupInfoFromGerrit($server, $group_name);

        return $this->http_client->isLastResponseSuccess();
    }

    public function listGroups(Git_RemoteServer_GerritServer $server ){
        return;
    }

    public function getAllGroups(Git_RemoteServer_GerritServer $server ){
        $this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Get all groups");

        $url = '/groups/';

        $options = $this->getOptionsForRequest($server, $url, array());

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            $groups = array();
            foreach ($this->decodeGerritResponse($this->http_client->getLastResponse()) as $name => $group) {
                $groups[$name] = $group['id'];
            }

            return $groups;
        } catch (Http_ClientException $exception) {
            return array();
        }
    }

    public function getGerritProjectName(GitRepository $repository) {
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        return $name_builder->getGerritProjectName($repository);
    }

    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name){
        if (! $this->userIsAlreadyMigratedOnGerrit($server, $user)) {
            return $this->createUserOnGerritAndAddItDirectlyIntoGroup($server, $user, $group_name);
        }

        $this->http_client->init();
        $url            = '/groups/'.$group_name.'/members/'.$user->getSSHUserName();
        $custom_options = array(
            CURLOPT_PUT => true,
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            return true;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name
     ) {
        //$this->http_client->init();

        $url            = '/groups/'.$group_name.'/members.delete/'.$user->getSSHUserName();
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            return true;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    private function createUserOnGerritAndAddItDirectlyIntoGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name
    ) {
        $this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Create user ". $user->getSSHUserName() ." in Gerrit and add it directly in $group_name group");

        $url_create_account = '/accounts/'.$user->getSSHUserName();

        $json_data = json_encode(
            array(
                'name'   => $user->getRealName(),
                'email'  => $user->getEmail(),
                'groups' => array($group_name)
            )
        );

        $custom_options = array(
            CURLOPT_PUT        => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_INFILE     => $this->body_builder->getTemporaryFile($json_data),
            CURLOPT_INFILESIZE => strlen($json_data),
        );

        $options = $this->getOptionsForRequest($server, $url_create_account, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            return true;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    private function userIsAlreadyMigratedOnGerrit(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user){
        $this->http_client->init();

        $url            = '/accounts/'.$user->getSSHUserName();
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST   => 'GET'
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->http_client->isLastResponseSuccess();
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name ){
        $exiting_members = $this->getAllMembers($server, $group_name);
        if (! $exiting_members) {
            return true;
        }

        //$this->http_client->init();
        $this->logger->info("Gerrit REST driver: Remove all group members from $group_name");

        $url = '/groups/'.$group_name.'/members.delete';

        $custom_options = array(
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_POSTFIELDS => json_encode(
                array(
                    'members' => $exiting_members
                )
            )
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->http_client->isLastResponseSuccess();
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name ){
        //$this->http_client->init();

        $url = '/groups/'. urlencode($group_name) .'/groups/'. urlencode($included_group_name);

        $custom_options = array(
            CURLOPT_PUT => true,
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->http_client->isLastResponseSuccess();
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name ){
        $exiting_groups = $this->getAllIncludedGroups($server, $group_name);
        if (! $exiting_groups) {
            return true;
        }

        //$this->http_client->init();
        $this->logger->info("Gerrit REST driver: Remove all included groups from group $group_name");

        $url = '/groups/'.urlencode($group_name).'/groups.delete';

        $custom_options = array(
            CURLOPT_POST       => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_POSTFIELDS => json_encode(
                array(
                    'groups' => $exiting_groups
                )
            )
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->http_client->isLastResponseSuccess();
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function flushGerritCacheAccounts($server ){
        return;
    }

    public function addSSHKeyToAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $ssh_key ){
        return;
    }

    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key
     ) {
        return;
    }

    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name ){
        //$this->http_client->init();
        $this->logger->info("Gerrit REST driver: Set project $parent_project_name as parent of $project_name");

        $url       = '/projects/'. urlencode($project_name) .'/parent';
        $json_data = json_encode(
            array(
                'parent' => $parent_project_name
            )
        );

        $custom_options = array(
            CURLOPT_PUT        => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_INFILE     => $this->body_builder->getTemporaryFile($json_data),
            CURLOPT_INFILESIZE => strlen($json_data),
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return true;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name ){
        return $this->setProjectInheritance($server, $project_name, self::DEFAULT_PARENT_PROJECT);
    }

    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server ){
        //$this->http_client->init();
        $this->logger->debug("Gerrit REST driver: Check if delete plugin is activated");

        $url            = '/plugins/';
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST => 'GET'
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            $plugins = $this->decodeGerritResponse($this->http_client->getLastResponse());

            $activated = isset($plugins['deleteproject']);

            $this->logger->debug("Gerrit REST driver: delete plugin is activated : $activated");

            return $activated;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name ){
        return;
    }

    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name ){
        $this->http_client->init();

        $url       = '/projects/'. urlencode($gerrit_project_full_name) .'/config';
        $json_data = json_encode(
            array(
                'state' => 'READ_ONLY'
            )
        );

        $custom_options = array(
            CURLOPT_PUT        => true,
            CURLOPT_HTTPHEADER => array(self::CONTENT_TYPE_JSON),
            CURLOPT_INFILE     => $this->body_builder->getTemporaryFile($json_data),
            CURLOPT_INFILESIZE => strlen($json_data),
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return true;
        } catch (Http_ClientException $exception) {
            return false;
        }
    }

    private function getGerritURL(Git_RemoteServer_GerritServer $server, $url) {
        $full_url = $server->getBaseUrl().'/a'. $url;

        return $full_url;
    }

    private function getOptionsForRequest(Git_RemoteServer_GerritServer $server, $url, array $custom_options) {
        $standard_options = array(
            CURLOPT_URL             => $this->getGerritURL($server, $url),
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_HTTPAUTH        => CURLAUTH_DIGEST,
            CURLOPT_USERPWD         => $server->getLogin() .':'. $server->getHTTPPassword(),
        );

        return ($standard_options + $custom_options);
    }

    private function getAllMembers(
        Git_RemoteServer_GerritServer $server,
        $group_name
    ) {
        //$this->http_client->init();

        $url     = '/groups/'.$group_name.'/members';
        $options = $this->getOptionsForRequest($server, $url, array(
           CURLOPT_CUSTOMREQUEST   => 'GET',
        ));

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            $gerrit_response = $this->http_client->getLastResponse();
            $members = $this->decodeGerritResponse($gerrit_response);

            return array_map(array($this, 'pluckUsername'), $members);
        } catch(Http_ClientException $exception) {
            return array();
        }
    }

    private function getAllIncludedGroups(
        Git_RemoteServer_GerritServer $server,
        $group_name
    ) {
        //$this->http_client->init();

        $url     = '/groups/'.urlencode($group_name).'/groups';
        $options = $this->getOptionsForRequest($server, $url, array(
           CURLOPT_CUSTOMREQUEST   => 'GET',
        ));

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();
            $gerrit_response = $this->http_client->getLastResponse();
            $members = $this->decodeGerritResponse($gerrit_response);

            return array_map(array($this, 'pluckGroupname'), $members);
        } catch(Http_ClientException $exception) {
            return array();
        }
    }

    private function pluckUsername($member) {
        return $member['username'];
    }

    private function pluckGroupname($member) {
        return $member['name'];
    }

    private function decodeGerritResponse($gerrit_response) {
        $magic_prefix = ")]}'";
        $regexp = '/^'.preg_quote($magic_prefix).'\s+/';

        return json_decode(preg_replace($regexp, '', $gerrit_response), true);
    }

    private function getGroupInfoFromGerrit($server, $group_name) {
        //$this->http_client->init();

        $url            = '/groups/'. urlencode($group_name);
        $custom_options = array(
            CURLOPT_CUSTOMREQUEST => 'GET'
        );

        $options = $this->getOptionsForRequest($server, $url, $custom_options);

        try {
            $this->http_client->addOptions($options);
            $this->http_client->doRequest();

            return $this->decodeGerritResponse($this->http_client->getLastResponse());
        } catch (Http_ClientException $exception) {
            return false;
        }
    }
}