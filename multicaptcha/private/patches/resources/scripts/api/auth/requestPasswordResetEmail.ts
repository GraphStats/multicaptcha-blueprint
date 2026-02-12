import http from '@/api/http';

export default (email: string, recaptchaData?: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/password', { email, 'g-recaptcha-response': recaptchaData, recaptchaData })
            .then((response: any) => resolve(response.data.data || 'If an account exists with that email, a password reset link has been sent.'))
            .catch(reject);
    });
};
