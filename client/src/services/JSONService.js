// @flow

export default class JSONService {
    static parseJSON(text: string) {
        try {
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    }
}
