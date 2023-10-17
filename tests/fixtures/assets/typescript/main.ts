import {CustomModule} from './dir/custom.module.ts';
import {Toast} from 'bootstrap';
CustomModule.printMessage();

let toast = new Toast(document.querySelector('.toast'));
toast.show();
