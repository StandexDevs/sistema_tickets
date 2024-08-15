<?php 

namespace App\Controllers;
use App\Models\UserModel;
use App\Models\PermisosGruposModel;
use App\Models\EventosUsersModel;
helper('permisos_helper');

class Auth extends \IonAuth\Controllers\Auth{
	/**
     * If you want to customize the views,
     *  - copy the ion-auth/Views/auth folder to your Views folder,
     *  - remove comment
	*/
	protected $viewsFolder = 'auth';

	public function index(){
		
		$data['identity'] = [
			'name'  => 'identity',
			'id'    => 'identity',
			'type'  => 'text',
			'value' => set_value('identity'),
		];

		$data['password'] = [
			'name' => 'password',
			'id'   => 'password',
			'type' => 'password',
		];

		if (! $this->ionAuth->loggedIn()){
			return view('auth/login', $data);
		}else{
			$data['title'] = lang('Auth.index_heading');
			$data['message'] = $this->validation->getErrors() ? $this->validation->listErrors($this->validationListTemplate) : $this->session->getFlashdata('message');
			$data['users'] = $this->ionAuth->users()->result();
			foreach ($data['users'] as $k => $user){
				$data['users'][$k]->groups = $this->ionAuth->getUsersGroups($user->id)->getResult();
			}

			return view('auth/login', $data);
		}
	}

	public function login(){
		$db = \Config\Database::connect();
		$this->data['title'] = lang('Auth.login_heading');

		// validate form input
		$this->validation->setRule('identity', str_replace(':', '', lang('Auth.login_identity_label')), 'required');
		$this->validation->setRule('password', str_replace(':', '', lang('Auth.login_password_label')), 'required');

		if ($this->request->getPost() && $this->validation->withRequest($this->request)->run()){
			$remember = (bool)$this->request->getVar('remember');
			//IonAuth valida si el user exite
			if ($this->ionAuth->login($this->request->getVar('identity'), $this->request->getVar('password'), $remember)){
				
				$user = $this->ionAuth->user()->row(); 

				$builderUG = $db->table('v_users_groups')->select('*')->where('user_id', $user->id);
				$queryUG = $builderUG->get();
				$usergroup = $queryUG->getRow();

				$builder = $db->table('v_users_evento')->select('*')->where('id_user', $user->id);
				$query = $builder->get();
				$userEvento = $query->getRow();
				
				if ($userEvento !== null) {
					$evento = $userEvento;
					$tipo = $userEvento->tipo_id;
				} else {
					$evento = "multi";
					$tipo = 'admin';
				}
				
				$this->session->set([
					'user' => $usergroup,
					'evento' => $evento,
					'permisos' => obtenerPermisosPorGrupo($usergroup->group_id)
				]);

				if ($userEvento !== null && in_array($userEvento->tipo_id, [3, 4, 6])) {
					return redirect()->to('/management')->withCookies();
				} else {
					return redirect()->to('/dashboard')->withCookies();
				}
			}else{
				$this->session->setFlashdata('message', $this->ionAuth->errors($this->validationListTemplate));
				return redirect()->back()->withInput();
			}

		}else{
			$data['message'] = $this->validation->getErrors() ? $this->validation->listErrors($this->validationListTemplate) : $this->session->getFlashdata('message');

			$data['identity'] = [
				'name'  => 'identity',
				'id'    => 'identity',
				'type'  => 'text',
				'value' => set_value('identity'),
			];

			$data['password'] = [
				'name' => 'password',
				'id'   => 'password',
				'type' => 'password',
			];

			return view('auth/login', $data);
		}
	}
	
	public function registrarUser(){
		
		$user = strtolower($this->request->getPost('usuario'));
		$email = strtolower($this->request->getPost('email'));
		$nombre = $this->request->getPost('nombre');
		$apellido = $this->request->getPost('apellido');
		$tipo = $this->request->getPost('tipo');
		$evento = $this->request->getPost('evento');
		$piso = $this->request->getPost('piso');
		$model = new EventosUsersModel();

		$additional_data = array(
			'first_name' =>  $nombre,
			'last_name' => $apellido,
		);
		
		$group = array((int)$tipo);
		if ($this->ionAuth->usernameCheck($user)){
			return $this->response->setJSON(["result" => "error", "message" => $this->ionAuth->messages()]);
		}

		$id = $this->ionAuth->register($user, '12345678', $email, $additional_data, $group);

		if ($id){

			$data = [
				'id_evento' => $evento,
				'id_user' => $id,
				'id_rol' => $tipo,
				'id_piso' => $piso
			];

			if ($model->insert($data)) {
				return $this->response->setJSON(["result" => "success", "message" => "Usuario registrado"]);
			} else {
				return $this->response->setJSON(["result" => "error", "message" => "Error al registrar al vehiculo"]);
			}
			
		} else {
			return $this->response->setJSON(["result" => "error", "message" => $this->ionAuth->errors()]);
		}
	}

	public function editarUser() {
		$model = new EventosUsersModel();
		// Obtener los datos del formulario
		$id = $this->request->getPost('id_user_input');
		$nombre = $this->request->getPost('nombre');
		$evento = $this->request->getPost('evento');
		$apellido = $this->request->getPost('apellido');
		$user = strtolower($this->request->getPost('usuario'));
		$email = strtolower($this->request->getPost('email'));
		$tipo = $this->request->getPost('tipo');
		$piso = $this->request->getPost('piso');

		// Preparar los datos para la actualización
		$data = [
			'username' => $user,
			'email' => $email,
			'first_name' => $nombre,
			'last_name' => $apellido,
		];

		// Obtener los grupos del usuario
		$user_groups = $this->ionAuth->getUsersGroups($id)->getResult();

		// Verificar si el tipo es el mismo donde que se envió como param
		$tipo_exists = array_filter($user_groups, function($group) use ($tipo) {
			return $group->id == $tipo;
		});

		// Actualizar solo los datos del usuario si es el mismo
		if ($tipo_exists) {
			if ($this->ionAuth->update($id, $data)) {

				$userInfo = $model->where('id_user', $id)->first();

				$data = [
					'id_evento' => $evento,
					'id_rol' => $tipo,
					'id_piso' => $piso
				];
				
				if ($model->update($userInfo["id"], $data)) {
					return $this->response->setJSON(["result" => "success", "message" => "Usuario actualizado"]);
				} else {
					return $this->response->setJSON(["result" => "error", "message" => "Error al registrar al vehiculo"]);
				}

			} else {
				return $this->response->setJSON(["result" => "error", "message" => $this->ionAuth->errors()]);
			}
		}

		// Eliminar al usuario de todos los grupos y agregarlo al nuevo grupo si el tipo no existe
		if ($this->ionAuth->removeFromGroup(false, $id) && $this->ionAuth->addToGroup($tipo, $id)) {
			if ($this->ionAuth->update($id, $data)) {
				return $this->response->setJSON(["result" => "success", "message" => "Usuario y grupo actualizados"]);
			} else {
				return $this->response->setJSON(["result" => "error", "message" => $this->ionAuth->errors()]);
			}
		} else {
			return $this->response->setJSON(["result" => "error", "message" => "Error al modificar el grupo del usuario"]);
    	}
	}

	public function eliminarUser(){
		$id = $this->request->getPost('id_user');

		if(!$this->ionAuth->removeFromGroup(null, $id)){
			return $this->response->setJSON(["result" => "success", "message" => "Error al retirar permisos del usuario"]);
		}
		
		if ($this->ionAuth->deleteUser($id)) {
			return $this->response->setJSON(["result" => "success", "message" => $this->ionAuth->messages()]);
		} else {
			return $this->response->setJSON(["result" => "error", "message" => $this->ionAuth->errors()]);
		}
	}

	public function logout(){
		$this->data['title'] = 'Logout';
		$this->ionAuth->logout();
		$this->session->setFlashdata('message', $this->ionAuth->messages());
		return redirect()->to('/auth/login')->withCookies();
	}

	public function obtener_roles(){
		$groups = $this->ionAuth->groups()->result();
		return $this->response->setJSON(["result" => "success", "data" => $groups]);
	}

}