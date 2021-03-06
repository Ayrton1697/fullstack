import { Component, OnInit } from '@angular/core';
import {Router, ActivatedRoute, Params} from '@angular/router';
import {UserService} from '../../services/user.service';
import {CategoryService} from '../../services/category.service';
import {Post} from '../../models/post';
import {global} from '../../services/global';
import {PostService} from '../../services/post.service';


 
@Component({
  selector: 'app-post-edit',
  templateUrl: '../post-new/post-new.component.html',
  providers: [UserService, CategoryService, PostService]
})
export class PostEditComponent implements OnInit {

	public page_title:string;
	public identity;
	public token;
  	public url;
	public post: Post;
	public is_edit:boolean;
  public status;
  public categories;
  public froala_options: Object = {
    charCounterCount: true,
    toolbarButtons: ['bold', 'italic', 'underline', 'paragraphFormat','alert'],
    toolbarButtonsXS: ['bold', 'italic', 'underline', 'paragraphFormat','alert'],
    toolbarButtonsSM: ['bold', 'italic', 'underline', 'paragraphFormat','alert'],
    toolbarButtonsMD: ['bold', 'italic', 'underline', 'paragraphFormat','alert'],
  };
  public afuConfig = {
    multiple: false,
    formatsAllowed: ".jpg,.png,.gif,.jpeg",
    maxSize: "50",
    uploadAPI:  {
      url:global.url+'post/upload',
      headers: {
     "Authorization" : this._userService.getToken()  
      }
    },
    theme: "attachPin",
    hideProgressBar: false,
    hideResetBtn: true,
    hideSelectBtn: false,
    attachPinText: 'Subir imagen'
};

  constructor(
	private _router: Router,
	private _route:ActivatedRoute,
	private _userService: UserService,
	private _categoryService: CategoryService,
 	private _postService: PostService

  	) {
  	this.page_title= 'Editar entrada';
  	this.identity= this._userService.getIdentity();
  	this.token = this._userService.getToken();
  	this.is_edit=true;
   }

  ngOnInit() { 

  	this.getCategories();
  	this.post= new Post(1,this.identity.sub,1,'','', null,null);
 	this.getPost();
    
  }

  getCategories(){
    this._categoryService.getCategories().subscribe(
      response=>{
        if (response.status =='success'){
          this.categories= response.categories;

        }
   },
      error=>{
        console.log(error);

      }

      );
  }
    getPost(){
  	//Conseguir ID que viene en la URL
  	this._route.params.subscribe(params=>{
  		let id= +params['id'];
  	
	  	//Peticion AJAX
	  	this._postService.getPost(id).subscribe(
	  		response=>{
	  			
	  			if(response.status== 'success'){
	  			this.post= response.post;
	  				
	  			}else{
	  				this._router.navigate(['/inicio']);
	  			}
	  		},
	  		error=>{
	  			console.log(error);
	  			this._router.navigate(['/inicio']);
	  		}
	  		);
  });
  }
  imageUpload(data){
   
    let image_data= JSON.parse(data.response);
    this.post.image=image_data.image;
  }

  onSubmit(form){
  	this._postService.update(this.token, this.post, this.post.id).subscribe(
  		response=>{
  			if (response.status == 'success'){
  				this.status= 'success';
  				//this.post= response.post;
  				this._router.navigate(['/entrada', this.post.id]);
  			}else{
  				this.status= 'error';
  			}
  		},
  		error=>{	
  			this.status= 'error';
  			console.log(error);
  		}

  		);
  }
 	
}
