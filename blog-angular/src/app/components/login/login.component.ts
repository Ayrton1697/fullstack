import { Component, OnInit } from '@angular/core';
import {Router, ActivatedRoute, Params} from '@angular/router';
import {User} from '../../models/user';
import {UserService} from '../../services/user.service';


@Component({
  selector: 'login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
  providers:[UserService]
})
export class LoginComponent implements OnInit {

		public page_title:string;
		public user:User;
		public status:string;
		public token;
		public identity;

		constructor(
			private _userService: UserService,
			private _router: Router,
			private _route : ActivatedRoute
			) { 
		this.page_title='Identificate';
		this.user= new User(1,'','','ROLE_USER','', '','','');
		}

  ngOnInit() {
  	this.logout(); //Se ejectura siempre. cierra sesion cuando llega el parametro 'sure' por la url
  }

  onSubmit(form){
  	this._userService.signup(this.user).subscribe(
	  	response => {
	  		//TOKEN
	  		if(response.status != 'error'){
	  			this.status= 'success';
	  			this.token= response;
	  			console.log(this.token);
	  			//OBJETO DE USUARIO IDENTIFICADO
			 	this._userService.signup(this.user, true).subscribe(
						  	response => {
						  			this.identity= response;
						  			console.log(this.identity);

						  		//Persistir datos usuario
						  					
						  			localStorage.setItem('token',this.token);
						  			localStorage.setItem('identity',JSON.stringify(this.identity));

						  			this._router.navigate(['inicio']);
						  	},
						  	error=>{
						  		this.status= 'error';
						  		console.log(<any>Error);
						  	}
						);


	  		}else{
	  			this.status='error';
	  		}

	  	},
	  	error=>{
	  		this.status= 'error';
	  		console.log(<any>Error);
	  	}
	);
  }

  logout(){
  	this._route.params.subscribe(params=>{
  		let logout= +params['sure'];

  		if (logout ==1){
  			localStorage.removeItem('identity');
  			localStorage.removeItem('token');

  			this.identity= null;
  			this.token=null;

  			//REDIRECCION

  			this._router.navigate(['inicio']);

  		}
  	});
  }

}
