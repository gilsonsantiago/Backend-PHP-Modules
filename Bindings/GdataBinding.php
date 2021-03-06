<?php
/**
 * File defining GdataBinding
 *
 * PHP Version 5.3
 *
 * @category   Backend
 * @package    Modules
 * @subpackage Bindings
 * @author     J Jurgens du Toit <jrgns@backend-php.net>
 * @copyright  2011 - 2012 Jade IT (cc)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @link       http://backend-php.net
 */
namespace Backend\Base\Bindings;
require_once 'Zend/Loader.php';
\Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
\Zend_Loader::loadClass('Zend_Gdata_Gapps');
use Backend\Core\Utilities\ServiceLocator;
use \Backend\Modules\Exception as ModuleException;
/**
 * Binding for GData connections.
 *
 * This class relies on the Zend library, so you need to ensure that it's on the
 * scripts include path. You can install Zend through PEAR:
 *
 *     pear channel-discover zend.googlecode.com/svn
 *     pear install --alldeps zend/zend
 *
 * There's currently a known compatability issue.
 * See http://framework.zend.com/issues/browse/ZF-11959 for more detail.
 *
 * @category   Backend
 * @package    Modules
 * @subpackage Bindings
 * @author     J Jurgens du Toit <jrgns@jrgns.net>
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @link       http://backend-php.net
 */
class GdataBinding extends Binding
{
    /**
     * API Key used to access the GApps API. Unused for now.
     *
     * @var string
     */
    protected $key;

    /**
     * API Secret to access the GApps API. Unused for now.
     *
     * @var string
     */
    protected $secret;

    /**
     * The client used to access the GApps API.
     *
     * @Zend_Gdata_Gapps
     */
    protected $client;

    /**
     * The constructor for the object.
     *
     * The settings array must contain the name of the entity to bind to, the name
     * of the connection to use, as well as the username and password for the admin
     * user that will be used to access the API. You should also provide a domain if
     * it's different that that of the admin user's password.
     *
     * @param array $settings The settings for the Service Connection
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        if (empty($settings['username']) || empty($settings['password'])) {
            throw new ModuleException('Missing Google Admin User Credentials');
        }
        if (empty($settings['domain'])) {
            $domain = explode($settings['username']);
            if (empty($domain[1])) {
                throw new ModuleException('Missing Google Domain');
            } else {
                $settings['domain'] = $domain[1];
            }
        }

        // Get the Client
        $httpClient = \Zend_Gdata_ClientLogin::getHttpClient($settings['username'], $settings['password'], \Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
        $this->client = new \Zend_Gdata_Gapps($httpClient, $settings['domain']);
    }

    /**
     * Find multiple instances of the resource.
     *
     * Don't specify any criteria to retrieve a full list of instances.
     *
     * @param array $conditions An array of conditions on which to filter the list.
     * @param array $options    An array of options.
     *
     * @return array An array of representations of the resource.
     */
    public function find(array $conditions = array(), array $options = array())
    {
        $result = $this->client->retrieveAllUsers();
        if (!$result) {
            return false;
        }
        $result = iterator_to_array($result);
        $result = array_map(array($this, 'mapObjectToModel'), $result);
        return $result;
    }

    /**
     * Create an instance on the source, and return the instance.
     *
     * @param array $data The data to create a new resource.
     *
     * @return \Backend\Interfaces\ModelInterface The created model.
     * @throws \Backend\Modules\Exception When the resource can't be created.
     */
    public function create(array $data)
    {
        try {
            $result = $this->client->createUser(
                $data['username'],
                $data['given_name'],
                $data['family_name'],
                $data['password']
            );
        } catch (\Zend_Gdata_Gapps_ServiceException $e) {
            throw new ModuleException('Cannot created requested resource', null, $e);
        }

        return $this->mapObjectToModel($result);
    }

    /**
     * Read and return the single, specified instance of the resource.
     *
     * @param mixed   $identifier   The unique identifier for the instance, or an
     * array containing criteria on which to search for the resource.
     * @param boolean $returnObject Return the UserEntry Object.
     *
     * @return \Backend\Interfaces\ModelInterface The identified model.
     * @throws \Backend\Modules\Exception When the resource can't be found.
     */
    public function read($identifier, $returnObject = false)
    {
        try {
            $result = $this->client->retrieveUser($identifier);
        } catch (\Exception $e) {
            throw new ModuleException(
                'Cannot retrieve requested information', null, $e
            );
        }
        return $returnObject ? $result : $this->mapObjectToModel($result);
    }

    /**
     * Refresh the specified instance on the source.
     *
     * This function is the logical counterpart to update, and receives data from
     * the source.
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to refresh.
     * Passed by reference.
     *
     * @return boolean If the refresh was successful or not.
     * @throws \Backend\Modules\Exception When the resource can't be refreshed.
     */
    public function refresh(\Backend\Interfaces\ModelInterface &$model)
    {
    }

    /**
     * Update the specified instance of the resource.
     *
     * This function is the logical counterpart to refresh, and sends data to
     * the source.
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to update.
     * Passed by reference.
     *
     * @return boolean If the update was successful or not.
     * @throws \Backend\Modules\Exception When the resource can't be updated.
     */
    public function update(\Backend\Interfaces\ModelInterface &$model)
    {
        $object = $this->read($model->getId(), true);
        $object = $this->mapModelToObject($model, $object);
        try {
            $object->save();
        } catch (\Exception $e) {
            throw new ModuleException('Cannot update the resourc', null, $e);
        }
        return true;
    }

    /**
     * Delete the specified instance of the resource
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to delete
     *
     * @return boolean If the deletion was succesful or not.
     * @throws \Backend\Modules\Exception When the resource can't be deleted.
     */
    public function delete(\Backend\Interfaces\ModelInterface &$model)
    {
        $identifier = $model->getId();
        try {
            $result = $this->client->deleteUser($identifier);
        } catch (\Exception $e) {
            throw new ModuleException(
                'Cannot delete the specified resource', null, $e
            );
        }
        return true;
    }

    /**
     * Map Zend_Gdata_Gapps_UserEntry objects to Backend Models
     *
     * @param Zend_Gdata_Gapps_UserEntry $object The object to map.
     *
     * @return \Backend\Interfaces\ModelInterface The model.
     */
    protected function mapObjectToModel(\Zend_Gdata_Gapps_UserEntry $object)
    {
        $name  = $object->getName();
        $login = $object->getLogin();
        $properties  = array(
            'given_name'  => $name->getGivenName(),
            'family_name' => $name->getFamilyName(),
            'username'    => $login->getUsername(),
            'password'    => $login->getPassword(),
            'admin'       => $login->getAdmin(),
            'active'      => !(boolean)$login->getSuspended(),
        );
        $object = new $this->className;
        $object->populate($properties);
        return $object;
    }

    /**
     * Map a Backend Model to a Zend_Gdata_Gapps_User Entry.
     *
     * The model should provide the following functions for this function to
     * work correctly:
     *
     * * getUsername()
     * * getPassword()
     * * getGivenName()
     * * getFamilyName()
     *
     * @param \Backend\Interfaces\ModelInterface $model The model to map.
     * @param \Zend_Gdata_Gapps_UserEntry        $user  The UserEntry object to
     * populate.
     *
     * @return Zend_Gdata_Gapps_UserEntry The object
     */
    protected function mapModelToObject(\Backend\Interfaces\ModelInterface $model,
        \Zend_Gdata_Gapps_UserEntry $user = null)
    {
        $user = $user === null ? $this->client->newUserEntry() : $user;
        $user->login = $this->client->newLogin();
        $user->login->username = $model->getUsername();
        $user->login->password = $model->getPassword();
        $user->name = $this->client->newName();
        $user->name->givenName = $model->getGivenName();
        $user->name->familyName = $model->getFamilyName();
        return $user;
    }
}
