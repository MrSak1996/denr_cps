import axios from 'axios';

export const ProductService = {
  async getProducts(userId) {
    const response = await axios.get('https://cps.denrcalabarzon.com/api/application-details', {
      params: { id: userId }
    });
         return {
    applications: response.data.data};
  },

   async getUserList(userId) {
    const response = await axios.get('https://cps.denrcalabarzon.com/api/getUserList')
    return response.data.data;
  },



 async getApplicationsByStatus(status,id) {
    const response = await axios.get('https://cps.denrcalabarzon.com/api/applicationStatus', {
      params: { status:status,office_id:id }
    });
      return {
    applications: response.data.data,
    count: response.data.total_count
  };
  },



  async updateStatus(applicationId, status) {
    try {
      const response = await axios.put(
        `https://cps.denrcalabarzon.com/api/applications/${applicationId}/status`,
        {
          status: status,
        }
      );

      return response.data; // Laravel response
    } catch (error) {
      console.error('Error updating application status:', error);
      throw error;
    }
  },
};
