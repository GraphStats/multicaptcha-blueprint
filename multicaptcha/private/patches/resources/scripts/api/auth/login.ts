import http from '@/api/http';

export interface LoginResponse {
    complete: boolean;
    intended?: string;
    confirmationToken?: string;
}

export default (data: { username: string; password: string; recaptchaData?: string }): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/login', {
            username: data.username,
            password: data.password,
            'g-recaptcha-response': data.recaptchaData,
            recaptchaData: data.recaptchaData,
        })
            .then((response: any) => resolve(response.data.data))
            .catch(reject);
    });
};
