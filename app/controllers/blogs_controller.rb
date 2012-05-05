class BlogsController < ApplicationController
  def index
    @blogs = Blog.released

    respond_to do |format|
      format.html
      format.json { render :json => @blogs }
    end
  end

  def show
    @blog = Blog.get(params[:id])

    respond_to do |format|
      format.html
      format.json { render :json => @blog }
    end
  end

end
