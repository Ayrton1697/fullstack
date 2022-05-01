import { Component, OnInit } from '@angular/core';
import {User} from '../../models/user';
import {UserService} from '../../services/user.service';

@Component({
  selector: 'register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
  providers: [UserService]
})
export class RegisterComponent implements OnInit {
	public page_title:string;
	public user:User;
	public status: string;

  constructor(
  	private _userService:UserService
  	) { 
  	this.page_title='Registrate';
  	this.user= new User(1,'','','ROLE_USER','', '','','');
										 
  }												

  ngOnInit() {
  	console.log('Componente de registro');
  }
  onSubmit(form){

  	this._userService.register(this.user).subscribe(
  		response=> {	
  			if(response.status== 'success'){
  				this.status= response.status;
  				form.reset();
  			}else{
  				this.status= 'error';
  			}
  			
  		},
  		Error=>{
  			console.log(<any>Error);
  			this.status= 'error';
  		}
		

  		);

  	
  }

}
